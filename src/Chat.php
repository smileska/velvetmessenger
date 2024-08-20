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
            $conn->username = $username;  // Store username on the connection object
            echo "User {$username} authenticated (Connection {$conn->resourceId})\n";
        }
    }
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'authentication':
                    $this->handleAuthentication($from, $data);
                    break;
                case 'reaction':
                    $this->handleReaction($data);
                    break;
                case 'private_message':
                    $this->handlePrivateMessage($data);  // Remove $from here
                    break;
                default:
                    echo "Unknown message type received\n";
            }
        } else {
            echo "Invalid message format received\n";
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

        // Store the message in the database
        $stmt = $this->pdo->prepare('INSERT INTO messages (sender, recipient, message) VALUES (:sender, :recipient, :message)');
        $stmt->execute([
            'sender' => $sender,
            'recipient' => $recipient,
            'message' => $message
        ]);

        $messageId = $this->pdo->lastInsertId();
        $data['id'] = $messageId;

        // Send to recipient
        if (isset($this->userConnections[$recipient])) {
            $this->userConnections[$recipient]->send(json_encode($data));
        }

        // Send back to sender
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