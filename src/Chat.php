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
            $stmt = $this->pdo->prepare('INSERT INTO chatroom_messages (chatroom_id, user_id, message) VALUES (:chatroom_id, :user_id, :message)');
            $stmt->execute([
                'chatroom_id' => $data['chatroom_id'],
                'user_id' => $data['sender_id'],
                'message' => $data['message']
            ]);
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO messages (sender, recipient, message) VALUES (:sender, :recipient, :message)');
            $stmt->execute([
                'sender' => $data['sender'],
                'recipient' => $data['recipient'],
                'message' => $data['message']
            ]);
        }
        foreach ($this->clients as $client) {
            $client->send($msg);
        }
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