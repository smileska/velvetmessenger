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

    public function __construct(PDO $pdo) {
        $this->clients = new \SplObjectStorage;
        $this->pdo = $pdo;
        $this->userConnections = [];
        $this->chatroomConnections = [];
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

    private function handleChatroomMessage(ConnectionInterface $from, $data) {
        $chatroomId = $data['chatroom_id'];
        echo "Handling chatroom message for room {$chatroomId}\n";

        if (isset($this->chatroomConnections[$chatroomId])) {
            foreach ($this->chatroomConnections[$chatroomId] as $client) {
                if ($client !== $from) {
                    $client->send(json_encode($data));
                    echo "Sent message to user in chatroom {$chatroomId} (Connection {$client->resourceId})\n";
                }
            }
        } else {
            echo "Error: Chatroom {$chatroomId} not found in connections\n";
        }
    }

    private function handlePrivateMessage(ConnectionInterface $from, $data) {
        $sender = $data['sender'];
        $recipient = $data['recipient'];
        echo "Handling private message from {$sender} to {$recipient}\n";

        if (isset($this->userConnections[$recipient])) {
            $this->userConnections[$recipient]->send(json_encode($data));
            echo "Sent private message to {$recipient}\n";
        } else {
            echo "Error: Recipient {$recipient} not found in connections\n";
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