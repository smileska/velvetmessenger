<?php

namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $pdo;

    public function __construct(PDO $pdo) {
        $this->clients = new \SplObjectStorage;
        $this->pdo = $pdo;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients);
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        $data = json_decode($msg, true);

        if (isset($data['type']) && $data['type'] === 'message') {
            $msg = $this->handleChatroomMessage($data);
        } elseif (isset($data['sender']) && isset($data['recipient']) && isset($data['message'])) {
            $this->handlePrivateMessage($data);
        } else {
            echo "Invalid message format received\n";
            return;
        }

        foreach ($this->clients as $client) {
            $client->send($msg);
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
        $stmt = $this->pdo->prepare('INSERT INTO messages (sender, recipient, message) VALUES (:sender, :recipient, :message)');
        $stmt->execute([
            'sender' => $data['sender'],
            'recipient' => $data['recipient'],
            'message' => $data['message']
        ]);
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}