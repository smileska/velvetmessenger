<?php

namespace Controllers;

use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use Repositories\Repository;

class AuthController
{
    private $pdo;
    private $repository;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->repository = new Repository($pdo);
    }

    public function login(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody();
        $username = $parsedBody['username'] ?? null;
        $password = $parsedBody['password'] ?? null;

        if ($username === null || $password === null) {
            $errors = "Please enter username and password";
            $html = view('login.view.php', ['errors' => $errors]);
            $response->getBody()->write($html);
            return $response->withStatus(400);
        }

        $user = $this->repository->fetchOne('users', ['*'], 'username = :username', ['username' => $username]) ?? null;

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['image'] = $user['image'];
            $this->updateUserStatus($username, 'Online');
            return $response->withHeader('Location', '/')->withStatus(302);
        } else {
            $errors = "Username or password is incorrect";
            $html = view('login.view.php', ['errors' => $errors]);
            $response->getBody()->write($html);
            return $response->withStatus(401);
        }
    }

    public function logout(Request $request, Response $response): Response
    {
        if (isset($_SESSION['username'])) {
            $this->updateUserStatus($_SESSION['username'], 'Offline');
        }
        session_destroy();
        return $response->withHeader('Location', '/')->withStatus(302);
    }

    private function updateUserStatus($username, $status)
    {
        $stmt = $this->pdo->prepare('UPDATE users SET status = :status WHERE username = :username');
        $stmt->execute(['status' => $status, 'username' => $username]);
    }
    public function showLogin(Request $request, Response $response): Response
    {
        $html = view('login.view.php');
                $response->getBody()->write($html);
                return $response;
    }
    public function verifyEmail(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody();
        $verificationCode = $parsedBody['verification_code'] ?? null;
        $email = $parsedBody['email'] ?? null;

        if (!$verificationCode || !$email) {
            $html = view('verify.view.php', ['errors' => ['Verification code or email is missing.'], 'email' => $email]);
            $response->getBody()->write($html);
            return $response->withStatus(400);
        }

        try {
            $this->pdo->beginTransaction();

            $user = $this->repository->fetchOne('unverified_users', ['*'], 'email = :email AND verification_code = :verification_code', ['email' => $email, 'verification_code' => $verificationCode]) ?? null;

            if ($user) {
                $stmt = $this->pdo->prepare('INSERT INTO users (username, password, email, image, status) VALUES (:username, :password, :email, :image, :status)');
                $stmt->execute([
                    'username' => $user['username'],
                    'password' => $user['password'],
                    'email' => $user['email'],
                    'image' => $user['image'],
                    'status' => 'Online'
                ]);

                $stmt = $this->pdo->prepare('DELETE FROM unverified_users WHERE email = :email');
                $stmt->execute(['email' => $email]);

                $this->pdo->commit();

                $html = view('email-verified.view.php');
                $response->getBody()->write($html);
                return $response->withStatus(200);
            } else {
                $this->pdo->rollBack();
                $html = view('verify.view.php', ['errors' => ['Invalid verification code.'], 'email' => $email]);
                $response->getBody()->write($html);
                return $response->withStatus(400);
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $response->getBody()->write("Error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

}