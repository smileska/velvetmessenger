<?php

namespace Controllers;

use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use Repositories\Repository;

class ProfileController
{
    private $pdo;
    private $repository;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->repository = new Repository($pdo);
    }

    public function updateUsername(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody();
        $newUsername = $parsedBody['new_username'] ?? '';
        $password = $parsedBody['password_for_username'] ?? '';

        if (!isset($_SESSION['user_id'])) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $userId = $_SESSION['user_id'];

        $user = $this->repository->fetchOne('users', ['password'], 'id = :id', ['id' => $userId]) ?? null;

        if (!$user || !password_verify($password, $user['password'])) {
            $html = view('profile.view.php', ['error' => 'Incorrect password']);
            $response->getBody()->write($html);
            return $response->withStatus(400);
        }

        $stmt = $this->pdo->prepare('UPDATE users SET username = :username WHERE id = :id');
        $stmt->execute(['username' => $newUsername, 'id' => $userId]);

        $_SESSION['username'] = $newUsername;

        return $response->withHeader('Location', '/profile')->withStatus(302);
    }

    public function updatePassword(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody();
        $currentPassword = $parsedBody['current_password'] ?? '';
        $newPassword = $parsedBody['new_password'] ?? '';
        $confirmNewPassword = $parsedBody['confirm_new_password'] ?? '';

        if (!isset($_SESSION['user_id'])) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }

        $userId = $_SESSION['user_id'];

        $user = $this->repository->fetchOne('users', ['password'], 'id = :id', ['id' => $userId]) ?? null;

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $html = view('profile.view.php', ['error' => 'Incorrect current password']);
            $response->getBody()->write($html);
            return $response->withStatus(400);
        }

        if ($newPassword !== $confirmNewPassword) {
            $html = view('profile.view.php', ['error' => 'New password and confirmation do not match']);
            $response->getBody()->write($html);
            return $response->withStatus(400);
        }

        $hashedNewPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute(['password' => $hashedNewPassword, 'id' => $userId]);

        return $response->withHeader('Location', '/profile')->withStatus(302);
    }
    public function updateProfilePicture(Request $request, Response $response): Response {
        $pdo = $this->pdo;

        $username = $_SESSION['username'];
        $uploadedFiles = $request->getUploadedFiles();
        $profilePicture = $uploadedFiles['profile_picture'] ?? null;
        $imagePath = 'ui/icons/default.png';

        if ($profilePicture instanceof UploadedFileInterface && $profilePicture->getError() === UPLOAD_ERR_OK) {
            $img_name = $profilePicture->getClientFilename();
            $img_ext = pathinfo($img_name, PATHINFO_EXTENSION);
            $allowedExtensions = ["jpeg", "png", "jpg"];

            if (in_array($img_ext, $allowedExtensions)) {
                $img_new_name = uniqid() . "." . $img_ext;
                $uploadDir = 'uploads/';
                $imagePath = $uploadDir . $img_new_name;
                $profilePicture->moveTo(__DIR__ . '/../public/' . $imagePath);

                try {
                    $stmt = $pdo->prepare('UPDATE users SET image = :image WHERE username = :username');
                    $stmt->execute(['image' => $imagePath, 'username' => $username]);
                    $_SESSION['image'] = $imagePath;
                    return $response->withHeader('Location', '/profile')->withStatus(302);
                } catch (PDOException $e) {
                    $response->getBody()->write("Error: " . $e->getMessage());
                    return $response->withStatus(500);
                }
            } else {
                $response->getBody()->write("Invalid image file type.");
                return $response->withStatus(400);
            }
        }

        $response->getBody()->write("Error uploading image.");
        return $response->withStatus(400);
    }
    public function showProfile(Request $request, Response $response): Response {
        $pdo = $this->pdo;
        $username = $_SESSION['username'];

        $user = $this->repository->fetchOne('users', ['*'], 'username = :username', ['username' => $username]) ?? null;
        if ($user) {
            $html = view('profile.view.php', ['user' => $user]);
            $response->getBody()->write($html);
            return $response;
        } else {
            return $response->withStatus(404)->write('User not found');
        }
    }
    function updateUserStatus($pdo, $username, $status)
    {
        $stmt = $pdo->prepare('UPDATE users SET status = :status WHERE username = :username');
        $stmt->execute(['status' => $status, 'username' => $username]);
    }
}