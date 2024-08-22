<?php

namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $pdo;
    protected $userConnections;
    protected $chatroomConnections;
    protected $notifications;

    public function __construct(PDO $pdo) {
        $this->clients = new \SplObjectStorage;
        $this->pdo = $pdo;
        $this->userConnections = [];
        $this->chatroomConnections = [];
        $this->notifications = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->username = null;
        echo "New connection! ({$conn->resourceId})\n";
    }

    private function handleAuthentication(ConnectionInterface $conn, $data) {
        $username = $data['username'];
        $conn->username = $username;
        $this->userConnections[$username] = $conn;

        if (isset($data['chatroomId'])) {
            $chatroomId = $data['chatroomId'];
            $conn->chatroomId = $chatroomId;
            if (!isset($this->chatroomConnections[$chatroomId])) {
                $this->chatroomConnections[$chatroomId] = new \SplObjectStorage;
            }
            $this->chatroomConnections[$chatroomId]->attach($conn);
            echo "User {$username} joined chatroom {$chatroomId}\n";
        }

        if (isset($this->notifications[$username])) {
            foreach ($this->notifications[$username] as $notification) {
                $conn->send(json_encode($notification));
            }
            unset($this->notifications[$username]);
        }

        echo "User {$username} authenticated (Connection {$conn->resourceId})\n";
    }
    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Raw message received from connection {$from->resourceId}: " . $msg . "\n";

        $data = json_decode($msg, true);

        if (!$data) {
            echo "Failed to parse JSON for connection {$from->resourceId}. Error: " . json_last_error_msg() . "\n";
            return;
        }

        if (!isset($data['type'])) {
            echo "Message type not set for connection {$from->resourceId}. Full message: " . $msg . "\n";
            return;
        }

        echo "Received message of type: {$data['type']} from connection {$from->resourceId}\n";

        switch ($data['type']) {
            case 'authentication':
                $this->handleAuthentication($from, $data);
                break;
            case 'message':
                $this->handleMessage($from, $data);
                break;
            case 'reaction':
                $this->handleReaction($data);
                break;
            default:
                echo "Unknown message type received: {$data['type']} from connection {$from->resourceId}\n";
        }
    }
    private function handleMessage(ConnectionInterface $from, $data) {
        if (isset($data['chatroom_id'])) {
            $this->handleChatroomMessage($from, $data);
        } else {
            $this->handlePrivateMessage($from, $data);
        }
    }
    private function handleReaction($data) {
        $messageId = $data['message_id'];
        $reactorUsername = $data['username'];
        $reactionType = $data['reaction_type'];
        $chatroomId = $data['chatroom_id'];

        $stmt = $this->pdo->prepare('
            INSERT INTO chatroom_message_reactions (chatroom_message_id, user_id, reaction_type) 
            VALUES (:message_id, (SELECT id FROM users WHERE username = :username), :reaction_type)
            ON CONFLICT (chatroom_message_id, user_id) 
            DO UPDATE SET reaction_type = :reaction_type
        ');
        $stmt->execute([
            'message_id' => $messageId,
            'username' => $reactorUsername,
            'reaction_type' => $reactionType
        ]);

        if (isset($this->chatroomConnections[$chatroomId])) {
            foreach ($this->chatroomConnections[$chatroomId] as $client) {
                $client->send(json_encode($data));
            }
        }
        $stmt = $this->pdo->prepare('SELECT username FROM users WHERE id = (SELECT user_id FROM chatroom_messages WHERE id = :message_id)');
        $stmt->execute(['message_id' => $messageId]);
        $recipientUsername = $stmt->fetchColumn();

        if ($recipientUsername && $recipientUsername !== $reactorUsername) {
            $notification = [
                'type' => 'notification',
                'content' => "{$reactorUsername} reacted to your message in chatroom {$chatroomId}"
            ];

            if (isset($this->userConnections[$recipientUsername])) {
                $this->userConnections[$recipientUsername]->send(json_encode($notification));
            } else {
                if (!isset($this->notifications[$recipientUsername])) {
                    $this->notifications[$recipientUsername] = [];
                }
                $this->notifications[$recipientUsername][] = $notification;
            }
        }
    }

    private function handleChatroomMessage(ConnectionInterface $from, $data) {
        $chatroomId = $data['chatroom_id'];
        $sender = $data['sender'];
        $message = $data['message'];
        $imageUrl = $data['image_url'] ?? null;
        echo "Handling chatroom message for room {$chatroomId}\n";

        $stmt = $this->pdo->prepare('INSERT INTO chatroom_messages (chatroom_id, user_id, message, image_url) VALUES (:chatroom_id, (SELECT id FROM users WHERE username = :username), :message, :image_url)');
        $stmt->execute([
            'chatroom_id' => $chatroomId,
            'username' => $sender,
            'message' => $message,
            'image_url' => $imageUrl
        ]);
        $messageId = $this->pdo->lastInsertId();

        $data['id'] = $messageId;

        if (isset($this->chatroomConnections[$chatroomId])) {
            foreach ($this->chatroomConnections[$chatroomId] as $client) {
                $client->send(json_encode($data));
                echo "Sent message to user in chatroom {$chatroomId} (Connection {$client->resourceId})\n";
            }
        } else {
            echo "Error: Chatroom {$chatroomId} not found in connections\n";
        }
    }

    private function handlePrivateMessage(ConnectionInterface $from, $data) {
        $sender = $data['sender'];
        $recipient = $data['recipient'];
        echo "Handling private message from {$sender} to {$recipient}\n";

        $stmt = $this->pdo->prepare('INSERT INTO messages (sender, recipient, message) VALUES (:sender, :recipient, :message)');
        $stmt->execute([
            'sender' => $sender,
            'recipient' => $recipient,
            'message' => $data['message']
        ]);
        $messageId = $this->pdo->lastInsertId();

        $data['id'] = $messageId;

        if (isset($this->userConnections[$recipient])) {
            $this->userConnections[$recipient]->send(json_encode($data));
            echo "Sent private message to {$recipient}\n";

            $notification = [
                'type' => 'notification',
                'content' => "New message from {$sender}"
            ];
            $this->userConnections[$recipient]->send(json_encode($notification));
        } else {
            echo "Error: Recipient {$recipient} not found in connections\n";
            if (!isset($this->notifications[$recipient])) {
                $this->notifications[$recipient] = [];
            }
            $this->notifications[$recipient][] = [
                'type' => 'notification',
                'content' => "New message from {$sender}"
            ];
        }

        if (isset($this->userConnections[$sender])) {
            $this->userConnections[$sender]->send(json_encode($data));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        if (isset($conn->username)) {
            unset($this->userConnections[$conn->username]);
            echo "User {$conn->username} disconnected\n";
        }
        if (isset($conn->chatroomId)) {
            $this->chatroomConnections[$conn->chatroomId]->detach($conn);
            echo "User left chatroom {$conn->chatroomId}\n";
        }
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}