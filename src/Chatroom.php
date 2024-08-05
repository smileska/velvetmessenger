<?php

namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;

class Chatroom
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($name, $ownerId)
    {
        $stmt = $this->pdo->prepare("INSERT INTO chatrooms (name, owner_id) VALUES (:name, :owner_id) RETURNING id");
        $stmt->execute(['name' => $name, 'owner_id' => $ownerId]);
        $chatroomId = $stmt->fetchColumn();
        $stmt = $this->pdo->prepare("INSERT INTO chatroom_users (chatroom_id, user_id, is_admin) VALUES (:chatroom_id, :user_id, TRUE)");
        $stmt->execute(['chatroom_id' => $chatroomId, 'user_id' => $ownerId]);
        return $chatroomId;
    }
    public function addUser($chatroomId, $userId, $isAdmin = false)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM chatroom_users WHERE chatroom_id = :chatroom_id AND user_id = :user_id");
        $stmt->execute(['chatroom_id' => $chatroomId, 'user_id' => $userId]);
        if ($stmt->fetch()) {
            return false;
        }
        $isAdminValue = $isAdmin ? 1 : 0;

        $stmt = $this->pdo->prepare("INSERT INTO chatroom_users (chatroom_id, user_id, is_admin) VALUES (:chatroom_id, :user_id, :is_admin)");
        return $stmt->execute(['chatroom_id' => $chatroomId, 'user_id' => $userId, 'is_admin' => $isAdminValue]);
    }


    public function removeUser($chatroomId, $userId) {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM chatroom_users WHERE chatroom_id = :chatroom_id AND user_id = :user_id');
            return $stmt->execute(['chatroom_id' => $chatroomId, 'user_id' => $userId]);
        } catch (PDOException $e) {
            error_log('Error removing user from chatroom: ' . $e->getMessage());
            return false;
        }
    }

    public function delete($chatroomId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM chatrooms WHERE id = :id");
        return $stmt->execute(['id' => $chatroomId]);
    }

    public function getMessages($chatroomId)
    {
        $stmt = $this->pdo->prepare("SELECT cm.*, u.username FROM chatroom_messages cm JOIN users u ON cm.user_id = u.id WHERE cm.chatroom_id = :chatroom_id ORDER BY cm.sent_at ASC");
        $stmt->execute(['chatroom_id' => $chatroomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addMessage($chatroomId, $userId, $message)
    {
        $stmt = $this->pdo->prepare("INSERT INTO chatroom_messages (chatroom_id, user_id, message) VALUES (:chatroom_id, :user_id, :message)");
        return $stmt->execute(['chatroom_id' => $chatroomId, 'user_id' => $userId, 'message' => $message]);
    }

    public function getChatroomData($chatroomId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM chatrooms WHERE id = :id");
        $stmt->execute(['id' => $chatroomId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getUserChatrooms($userId)
    {
        $stmt = $this->pdo->prepare("
        SELECT c.id, c.name 
        FROM chatrooms c
        JOIN chatroom_users cu ON c.id = cu.chatroom_id
        WHERE cu.user_id = :user_id
    ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function isAdmin($chatroomId, $userId)
    {
        $stmt = $this->pdo->prepare("SELECT is_admin FROM chatroom_users WHERE chatroom_id = :chatroom_id AND user_id = :user_id");
        $stmt->execute(['chatroom_id' => $chatroomId, 'user_id' => $userId]);
        return (bool) $stmt->fetchColumn();
    }
    public function isUserInChatroom($chatroomId, $userId)
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM chatroom_users WHERE chatroom_id = :chatroom_id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            'chatroom_id' => $chatroomId,
            'user_id' => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function getSuggestedUsers($chatroomId)
    {
        $stmt = $this->pdo->prepare("
        SELECT su.user_id, u.username
        FROM suggested_users su
        JOIN users u ON su.user_id = u.id
        WHERE su.chatroom_id = :chatroom_id
    ");
        $stmt->execute(['chatroom_id' => $chatroomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}