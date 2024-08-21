<?php

namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $pdo;
    protected $userConnections;

    public function __construct(PDO $pdo) {
        $this->clients = new \SplObjectStorage;
        $this->pdo = $pdo;
        $this->userConnections = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    private function handleAuthentication(ConnectionInterface $conn, $data) {
        if (isset($data['username'])) {
            $username = $data['username'];
            $this->userConnections[$username] = $conn;
            $conn->username = $username;
            echo "User {$username} authenticated (Connection {$conn->resourceId})\n";
        }
    }
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if (!$data || !isset($data['type'])) {
            echo "Invalid message format received\n";
            return;
        }

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
                echo "Unknown message type received: {$data['type']}\n";
        }
    }
    private function handleMessage(ConnectionInterface $from, $data)
    {
        if (isset($data['chatroom_id'])) {
            $chatroomId = $data['chatroom_id'];
            $senderId = $data['sender_id'];
            $message = $data['message'];
            $imageUrl = $data['image_url'] ?? null;
            $messageId = $data['id'];

            $stmt = $this->pdo->prepare('INSERT INTO chatroom_messages (id, chatroom_id, user_id, message, image_url) VALUES (:id, :chatroom_id, :user_id, :message, :image_url)');
            $stmt->execute([
                'id' => $messageId,
                'chatroom_id' => $chatroomId,
                'user_id' => $senderId,
                'message' => $message,
                'image_url' => $imageUrl
            ]);

            foreach ($this->clients as $client) {
                if (isset($client->chatroomId) && $client->chatroomId == $chatroomId) {
                    $client->send(json_encode($data));
                }
            }

            echo "Message sent to chatroom {$chatroomId}\n";
        } else {
            $sender = $data['sender'];
            $recipient = $data['recipient'];
            $message = $data['message'];
            $imageUrl = $data['image_url'] ?? null;
            $messageId = $data['id'];

            $stmt = $this->pdo->prepare('INSERT INTO messages (id, sender, recipient, message, image_url) VALUES (:id, :sender, :recipient, :message, :image_url)');
            $stmt->execute([
                'id' => $messageId,
                'sender' => $sender,
                'recipient' => $recipient,
                'message' => $message,
                'image_url' => $imageUrl
            ]);

            if (isset($this->userConnections[$recipient])) {
                $this->userConnections[$recipient]->send(json_encode($data));
            }

            echo "Message sent from {$sender} to {$recipient}\n";
        }
    }
    private function handleReaction($data) {
        if (isset($data['chatroom_id'])) {
            $stmt = $this->pdo->prepare('
            INSERT INTO chatroom_message_reactions (chatroom_message_id, user_id, reaction_type) 
            VALUES (:message_id, :user_id, :reaction_type)
            ON CONFLICT (chatroom_message_id, user_id) 
            DO UPDATE SET reaction_type = :reaction_type
        ');
        } else {
            $stmt = $this->pdo->prepare('
            INSERT INTO message_reactions (message_id, user_id, reaction_type) 
            VALUES (:message_id, :user_id, :reaction_type)
            ON CONFLICT (message_id, user_id) 
            DO UPDATE SET reaction_type = :reaction_type
        ');
        }
        $stmt->execute([
            'message_id' => $data['message_id'],
            'user_id' => $data['user_id'],
            'reaction_type' => $data['reaction_type']
        ]);
        foreach ($this->clients as $client) {
            $client->send(json_encode($data));
        }
    }

    private function handleChatroomMessage($data) {
        if (!isset($data['chatroom_id']) || !isset($data['sender_id']) || !isset($data['message'])) {
            echo "Invalid chatroom message format\n";
            return;
        }

        $stmt = $this->pdo->prepare('SELECT username FROM users WHERE id = :user_id');
        $stmt->execute(['user_id' => $data['sender_id']]);
        $sender = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare('INSERT INTO chatroom_messages (chatroom_id, user_id, message) VALUES (:chatroom_id, :user_id, :message)');
        $stmt->execute([
            'chatroom_id' => $data['chatroom_id'],
            'user_id' => $data['sender_id'],
            'message' => $data['message']
        ]);

        $data['username'] = $sender['username'];

        return json_encode($data);
    }

    private function handlePrivateMessage($data) {
        $sender = $data['sender'];
        $recipient = $data['recipient'];
        $message = $data['message'];
        $stmt = $this->pdo->prepare('INSERT INTO messages (sender, recipient, message) VALUES (:sender, :recipient, :message)');
        $stmt->execute([
            'sender' => $sender,
            'recipient' => $recipient,
            'message' => $message
        ]);

        $messageId = $this->pdo->lastInsertId();
        $data['id'] = $messageId;
        if (isset($this->userConnections[$recipient])) {
            $this->userConnections[$recipient]->send(json_encode($data));
        }
        if (isset($this->userConnections[$sender])) {
            $this->userConnections[$sender]->send(json_encode($data));
        }

        echo "Private message sent from {$sender} to {$recipient}\n";
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        if (isset($conn->username)) {
            unset($this->userConnections[$conn->username]);
        }
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}