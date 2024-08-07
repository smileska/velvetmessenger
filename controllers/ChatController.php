<?php

namespace Controllers;

use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class ChatController
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function sendMessage(Request $request, Response $response): Response
    {
        $pdo = $this->pdo;
        if (!isset($_SESSION['username'])) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $parsedBody = $request->getParsedBody();
        $sender = $_SESSION['username'];
        $recipient = $parsedBody['recipient'] ?? '';
        $message = $parsedBody['message'] ?? '';
        $chatroomId = $parsedBody['chatroom_id'] ?? null;

        if (empty($message) || (empty($recipient) && empty($chatroomId))) {
            $response->getBody()->write('Recipient or chatroom and message are required.');
            return $response->withStatus(400);
        }

        try {
            if ($chatroomId) {
                $stmt = $pdo->prepare('INSERT INTO chatroom_messages (chatroom_id, sender, message) VALUES (:chatroom_id, :sender, :message)');
                $stmt->execute(['chatroom_id' => $chatroomId, 'sender' => $sender, 'message' => $message]);
                return $response->withHeader('Location', '/chatroom/' . $chatroomId)->withStatus(302);
            } else {
                $stmt = $pdo->prepare('INSERT INTO messages (sender, recipient, message) VALUES (:sender, :recipient, :message)');
                $stmt->execute(['sender' => $sender, 'recipient' => $recipient, 'message' => $message]);
                return $response->withHeader('Location', '/chat/' . $recipient)->withStatus(302);
            }
        } catch (PDOException $e) {
            $response->getBody()->write("Error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function getMessages(Request $request, Response $response, $args): Response
    {
        $pdo = $this->pdo;
        $sender = $_SESSION['username'];
        $recipient = $args['recipient'];

        $stmt = $pdo->prepare('
        SELECT m.*, mr.reaction_type 
        FROM messages m
        LEFT JOIN message_reactions mr ON m.id = mr.message_id AND mr.user_id = :user_id
        WHERE (m.sender = :sender AND m.recipient = :recipient) OR (m.sender = :recipient AND m.recipient = :sender) 
        ORDER BY m.timestamp ASC
    ');
        $stmt->execute([
            'sender' => $sender,
            'recipient' => $recipient,
            'user_id' => $_SESSION['user_id']
        ]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($messages));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function reactToMessage(Request $request, Response $response, array $args): Response {
        $messageId = $args['id'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        $reactionType = $request->getParsedBody()['reactionType'] ?? null;

        error_log("Received reaction request: " . json_encode(['messageId' => $messageId, 'userId' => $userId, 'reactionType' => $reactionType]));

        if (!$messageId || !$userId || !$reactionType) {
            error_log("Invalid message ID, user ID, or reaction type");
            $data = ['success' => false, 'error' => 'Invalid message ID, user ID, or reaction type'];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $pdo = $this->pdo;

        try {
            $stmt = $pdo->prepare('
            INSERT INTO message_reactions (message_id, user_id, reaction_type) 
            VALUES (:message_id, :user_id, :reaction_type)
            ON CONFLICT (message_id, user_id) 
            DO UPDATE SET reaction_type = :reaction_type
        ');
            $result = $stmt->execute([
                'message_id' => $messageId,
                'user_id' => $userId,
                'reaction_type' => $reactionType
            ]);

            if ($result) {
                error_log("Reaction saved successfully");
                $data = ['success' => true];
            } else {
                error_log("Failed to save reaction: " . json_encode($stmt->errorInfo()));
                $data = ['success' => false, 'error' => 'Failed to save reaction'];
            }
        } catch (PDOException $e) {
            error_log("PDO Exception: " . $e->getMessage());
            $data = ['success' => false, 'error' => $e->getMessage()];
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function getPreviousMessages(Request $request, Response $response): Response {
        $pdo = $this->pdo;
        $sender = $request->getQueryParams()['sender'] ?? '';
        $recipient = $request->getQueryParams()['recipient'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM messages WHERE (sender = :sender AND recipient = :recipient) OR (sender = :recipient AND recipient = :sender) ORDER BY timestamp ASC');
        $stmt->execute(['sender' => $sender, 'recipient' => $recipient]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $payload = json_encode($messages);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
    public function showChat(Request $request, Response $response, array $args): Response {
        $pdo = $this->pdo;
        if (!isset($_SESSION['username'])) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $currentUser = $_SESSION['username'];
        $chatUser = $args['username'];

        if ($currentUser === $chatUser) {
            return $response->withHeader('Location', '/')->withStatus(400);
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $chatUser]);
        $user = $stmt->fetch();

        if (!$user) {
            $response->getBody()->write('User not found');
            return $response->withStatus(404);
        }

        $html = view('chat.view.php', ['chatUser' => $user]);
        $response->getBody()->write($html);
        return $response;
    }
}