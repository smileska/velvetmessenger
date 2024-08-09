<?php

namespace Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use App\Chatroom;

class ChatroomController
{
    private $pdo;
    private $chatroom;

    public function __construct(PDO $pdo, Chatroom $chatroom)
    {
        $this->pdo = $pdo;
        $this->chatroom = $chatroom;
    }

    public function createChatroom(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $ownerId = (int)$_SESSION['user_id'];

        if ($ownerId === 0) {
            $response->getBody()->write('Invalid user ID');
            return $response->withStatus(400);
        }

        try {
            $chatroomId = $this->chatroom->create($data['name'], $ownerId);
            return $response->withHeader('Location', '/')->withStatus(302);
        } catch (PDOException $e) {
            error_log('Error creating chatroom: ' . $e->getMessage());
            $response->getBody()->write('Error creating chatroom');
            return $response->withStatus(500);
        }
    }

    public function getChatrooms(Request $request, Response $response): Response
    {
        if (!isset($_SESSION['user_id'])) {
            $response->getBody()->write('User not authenticated');
            return $response->withStatus(401);
        }
        $userId = (int)$_SESSION['user_id'];
        $chatrooms = $this->chatroom->getUserChatrooms($userId);
        $response->getBody()->write(json_encode($chatrooms));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function showChatroom(Request $request, Response $response, array $args): Response
    {
        if (!isset($_SESSION['user_id'])) {
            $response->getBody()->write(json_encode(['message' => 'User not authenticated']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $chatroomId = (int)$args['id'];
        $chatroomData = $this->chatroom->getChatroomData($chatroomId);

        if (!$chatroomData) {
            $response->getBody()->write(json_encode(['message' => 'Chatroom not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $html = view('chatroom.view.php', [
            'chatroomId' => $chatroomId,
            'chatroomName' => $chatroomData['name']
        ]);

        $response->getBody()->write($html);
        return $response;
    }

    public function getUserRole(Request $request, Response $response, array $args): Response
    {
        $chatroomId = (int)$args['id'];
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $data = ['role' => 'guest'];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $isAdmin = $this->chatroom->isAdmin($chatroomId, $userId);
        $role = $isAdmin ? 'admin' : 'user';

        $data = ['role' => $role];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function addUser(Request $request, Response $response, array $args): Response
    {
        $pdo = $this->pdo;
        $chatroom = $this->chatroom;

        $chatroomId = (int)$args['id'];
        $data = $request->getParsedBody();
        $username = $data['username'] ?? null;
        $currentUserId = $_SESSION['user_id'] ?? null;

        if (!$currentUserId) {
            $response->getBody()->write(json_encode(['message' => 'User not authenticated']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            if (!$chatroom->isAdmin($chatroomId, $currentUserId)) {
                throw new \Exception("Only admins can add users to the chatroom");
            }

            if (!$username) {
                throw new \Exception("Username is missing from request");
            }

            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
            $stmt->execute(['username' => $username]);
            $userId = $stmt->fetchColumn();

            if (!$userId) {
                throw new \Exception("User not found: $username");
            }

            $result = $chatroom->addUser($chatroomId, $userId);

            if (!$result) {
                throw new \Exception("Failed to add user: $username to chatroom: $chatroomId");
            }

            $response->getBody()->write(json_encode(['message' => 'User added successfully']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'message' => $e->getMessage(),
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function suggestUser(Request $request, Response $response, array $args): Response
    {
        $pdo = $this->pdo;
        $chatroomId = (int)$args['id'];
        $data = $request->getParsedBody();
        $username = $data['username'] ?? null;

        $currentUserId = $_SESSION['user_id'] ?? null;

        if (!$currentUserId) {
            $response->getBody()->write(json_encode(['message' => 'User not authenticated']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            $chatroom = $this->chatroom;
            if (!$chatroom->isUserInChatroom($chatroomId, $currentUserId)) {
                throw new Exception("User is not in the chatroom");
            }

            if (!$username) {
                throw new Exception("Username is missing from request");
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $suggestedUserId = $stmt->fetchColumn();

            if (!$suggestedUserId) {
                throw new Exception("User not found: $username");
            }

            $stmt = $pdo->prepare("SELECT * FROM suggested_users WHERE chatroom_id = :chatroom_id AND suggested_user_id = :suggested_user_id");
            $stmt->execute(['chatroom_id' => $chatroomId, 'suggested_user_id' => $suggestedUserId]);

            if ($stmt->rowCount() > 0) {
                throw new Exception("User has already been suggested");
            }

            $stmt = $pdo->prepare('INSERT INTO suggested_users (chatroom_id, suggested_user_id, suggested_by_user_id) VALUES (:chatroom_id, :suggested_user_id, :suggested_by_user_id)');
            $stmt->execute([
                'chatroom_id' => $chatroomId,
                'suggested_user_id' => $suggestedUserId,
                'suggested_by_user_id' => $currentUserId
            ]);

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'User suggestion added successfully']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
    public function getSuggestedUsers(Request $request, Response $response, array $args): Response
    {
        $pdo = $this->pdo;
        $chatroomId = (int)$args['id'];

        $stmt = $pdo->prepare('
        SELECT u.id, u.username 
        FROM suggested_users su 
        JOIN users u ON su.suggested_user_id = u.id 
        WHERE su.chatroom_id = :chatroom_id
    ');
        $stmt->execute(['chatroom_id' => $chatroomId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
    public function approveSuggestion(Request $request, Response $response, array $args): Response
    {
        $pdo = $this->pdo;
        $chatroomId = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $data['user_id'] ?? null;

        $currentUserId = $_SESSION['user_id'] ?? null;

        if (!$currentUserId) {
            $response->getBody()->write(json_encode(['message' => 'User not authenticated']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            $chatroom = $this->chatroom;
            if (!$chatroom->isAdmin($chatroomId, $currentUserId)) {
                throw new Exception("Only admins can approve suggestions");
            }

            if (!$userId) {
                throw new Exception("User ID is missing from request");
            }

            $stmt = $pdo->prepare('INSERT INTO chatroom_users (chatroom_id, user_id, is_admin) VALUES (:chatroom_id, :user_id, FALSE)');
            $stmt->execute(['chatroom_id' => $chatroomId, 'user_id' => $userId]);

            $stmt = $pdo->prepare('DELETE FROM suggested_users WHERE chatroom_id = :chatroom_id AND suggested_user_id = :suggested_user_id');
            $stmt->execute(['chatroom_id' => $chatroomId, 'suggested_user_id' => $userId]);

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Suggestion approved and user added to chatroom']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function deleteSuggestion(Request $request, Response $response, array $args): Response
    {
        $pdo = $this->pdo;
        $chatroomId = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $data['user_id'] ?? null;

        $currentUserId = $_SESSION['user_id'] ?? null;

        if (!$currentUserId) {
            $response->getBody()->write(json_encode(['message' => 'User not authenticated']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            $chatroom = $this->chatroom;
            if (!$chatroom->isAdmin($chatroomId, $currentUserId)) {
                throw new Exception("Only admins can delete suggestions");
            }

            if (!$userId) {
                throw new Exception("User ID is missing from request");
            }

            $stmt = $pdo->prepare('DELETE FROM suggested_users WHERE chatroom_id = :chatroom_id AND suggested_user_id = :suggested_user_id');
            $stmt->execute(['chatroom_id' => $chatroomId, 'suggested_user_id' => $userId]);

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Suggestion deleted successfully']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function getUsers(Request $request, Response $response, array $args): Response{
        $pdo = $this->pdo;
        $chatroomId = $args['id'];

        $stmt = $pdo->prepare('
    SELECT u.username, cu.is_admin 
    FROM chatroom_users cu 
    JOIN users u ON cu.user_id = u.id 
    WHERE cu.chatroom_id = :chatroom_id
    ');
        $stmt->execute(['chatroom_id' => $chatroomId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($users));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function removeUser(Request $request, Response $response, array $args): Response{
        $chatroom = $this->chatroom;
        $pdo = $this->pdo;
        $chatroomId = $args['id'];
        $data = $request->getParsedBody();
        $username = $data['username'] ?? null;
        $currentUserId = $_SESSION['user_id'];

        try {
            if (!$chatroom->isAdmin($chatroomId, $currentUserId)) {
                throw new Exception("Only admins can remove users from the chatroom");
            }

            if (!$username) {
                throw new Exception("Username is missing from request");
            }

            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
            $stmt->execute(['username' => $username]);
            $userId = $stmt->fetchColumn();

            if (!$userId) {
                throw new Exception("User not found: $username");
            }

            $result = $chatroom->removeUser($chatroomId, $userId);

            if (!$result) {
                throw new Exception("Failed to remove user: $username from chatroom: $chatroomId");
            }

            $response->getBody()->write(json_encode(['message' => 'User removed successfully']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'message' => 'An error occurred while processing your request',
                'error' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    public function leaveRoom(Request $request, Response $response, array $args): Response {
        $chatroom = $this->chatroom;
        $chatroomId = $args['id'];
        $userId = $_SESSION['user_id'];
        $result = $chatroom->removeUser($chatroomId, $userId);

        if ($result) {
            $response->getBody()->write(json_encode(['message' => 'Left chatroom successfully']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $response->getBody()->write(json_encode(['message' => 'Failed to leave chatroom']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
    public function getMessages(Request $request, Response $response, array $args): Response {
        $chatroom = $this->chatroom;
        $chatroomId = $args['id'];
        $messages = $chatroom->getMessages($chatroomId);

        $response->getBody()->write(json_encode($messages));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
    public function isAdmin(Request $request, Response $response, array $args): Response {
        $chatroom = $this->chatroom;
        $chatroomId = $args['id'];
        $userId = $_SESSION['user_id'];

        $isAdmin = $chatroom->isAdmin($chatroomId, $userId);

        $response->getBody()->write(json_encode(['isAdmin' => $isAdmin]));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function grantAdmin(Request $request, Response $response, array $args): Response {
        $pdo = $this->pdo;
        $chatroom = $this->chatroom;
        $chatroomId = (int)$args['id'];
        $data = $request->getParsedBody();
        $username = $data['username'] ?? null;
        $currentUserId = $_SESSION['user_id'] ?? null;

        if (!$currentUserId) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'User not authenticated']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            if (!$chatroom->isAdmin($chatroomId, $currentUserId)) {
                throw new Exception("Only admins can grant admin privileges");
            }

            if (!$username) {
                throw new Exception("Username is missing from request");
            }

            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
            $stmt->execute(['username' => $username]);
            $userId = $stmt->fetchColumn();

            if (!$userId) {
                throw new Exception("User not found: $username");
            }

            $stmt = $pdo->prepare('UPDATE chatroom_users SET is_admin = TRUE WHERE chatroom_id = :chatroom_id AND user_id = :user_id');
            $result = $stmt->execute(['chatroom_id' => $chatroomId, 'user_id' => $userId]);

            if (!$result) {
                throw new Exception("Failed to grant admin privileges to user: $username");
            }

            $response->getBody()->write(json_encode(['success' => true, 'message' => "Admin privileges granted to $username"]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }
    public function reactToChatroomMessage(Request $request, Response $response, array $args): Response {
        $messageId = $args['id'];
        $userId = $_SESSION['user_id'];
        $reactionType = $request->getParsedBody()['reactionType'];

        $pdo = $this->pdo;

        $stmt = $pdo->prepare('
    INSERT INTO chatroom_message_reactions (chatroom_message_id, user_id, reaction_type) 
    VALUES (:message_id, :user_id, :reaction_type)
    ON CONFLICT (chatroom_message_id, user_id) 
    DO UPDATE SET reaction_type = :reaction_type
');
        $result = $stmt->execute([
            'message_id' => $messageId,
            'user_id' => $userId,
            'reaction_type' => $reactionType
        ]);

        $data = ['success' => $result];
        $response->getBody()->write(json_encode($data));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
