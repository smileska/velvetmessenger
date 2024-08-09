<?php

namespace Controllers;

use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PDO;

class UserController
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function register(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();

        $username = $parsedBody['username'] ?? null;
        $password = $parsedBody['password'] ?? null;
        $confirm_password = $parsedBody['confirm_password'] ?? null;
        $email = $parsedBody['email'] ?? null;
        $image = $uploadedFiles['image'] ?? null;
        $imagePath = 'ui/icons/default.png';

        if ($image instanceof UploadedFileInterface && $image->getError() === UPLOAD_ERR_OK) {
            $img_name = $image->getClientFilename();
            $img_ext = pathinfo($img_name, PATHINFO_EXTENSION);
            $allowedExtensions = ["jpeg", "png", "jpg"];

            if (in_array($img_ext, $allowedExtensions)) {
                $img_new_name = uniqid() . "." . $img_ext;
                $uploadDir = 'uploads/';
                $imagePath = $uploadDir . $img_new_name;
                $image->moveTo(__DIR__ . '/../public/' . $imagePath);
            } else {
                $errors[] = "Invalid image file type.";
            }
        }

        $violations = validateUserData($username, $email, $password, $confirm_password);
        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = $violation->getMessage();
            }
            $html = view('register.view.php', ['errors' => $messages]);
            $response->getBody()->write($html);
            return $response->withStatus(400);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $verificationCode = bin2hex(random_bytes(16));

        try {
            $stmt = $this->pdo->prepare("INSERT INTO unverified_users (username, password, email, image, verification_code) VALUES (:username, :password, :email, :image, :verification_code)");
            $stmt->execute([
                'username' => $username,
                'password' => $hashedPassword,
                'email' => $email,
                'image' => $imagePath,
                'verification_code' => $verificationCode
            ]);

            $mail = new PHPMailer(true);
            try {
                // server settings
                $mail->isSMTP();
                $mail->Host = 'mailhog';
                $mail->SMTPAuth = false;
                $mail->Port = 1025;

                // recipients
                $mail->setFrom('velvetmessenger@example.com', 'Velvet Messenger');
                $mail->addAddress($email);

                // content
                $mail->isHTML(true);
                $mail->Subject = 'Email Verification';
                $mail->Body = 'Your verification code is: ' . $verificationCode;

                $mail->send();
            } catch (Exception $e) {
                $errors[] = 'Error sending verification email: ' . $mail->ErrorInfo;
                $html = view('register.view.php', ['errors' => $errors]);
                $response->getBody()->write($html);
                return $response->withStatus(500);
            }

            $html = view('verify.view.php', ['email' => $email]);
            $response->getBody()->write($html);
            return $response->withStatus(200);
        } catch (PDOException $e) {
            $response->getBody()->write("Error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }
    public function showRegister(Request $request, Response $response): Response{
        $html = view('register.view.php');
        $response->getBody()->write($html);
        return $response;
    }
    public function toggleDarkMode(Request $request, Response $response): Response {
        $darkMode = $_SESSION['dark_mode'] ?? false;
        $_SESSION['dark_mode'] = !$darkMode;

        $data = ['dark_mode' => $_SESSION['dark_mode']];
        $response->getBody()->write(json_encode($data));

        return $response->withHeader('Content-Type', 'application/json');
    }
    public function search(Request $request, Response $response): Response {
        $pdo = $this->pdo;
        $parsedBody = $request->getParsedBody();
        $searchQuery = $parsedBody['search_user'] ?? '';

        if (empty($searchQuery)) {
            $errors = "Please enter a username to search.";
            $html = view('index.view.php', ['errors' => $errors]);
            $response->getBody()->write($html);
            return $response->withStatus(400);
        }

        $stmt = $pdo->prepare('SELECT username, image FROM users WHERE username LIKE :searchQuery');
        $stmt->execute(['searchQuery' => '%' . $searchQuery . '%']);
        $users = $stmt->fetchAll();

        $html = view('index.view.php', ['users' => $users, 'searchQuery' => $searchQuery]);
        $response->getBody()->write($html);
        return $response;
    }
    public function showUserProfile(Request $request, Response $response, array $args): Response {
        $pdo = $this->pdo;

        $profileUsername = $args['username'];

        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $profileUsername]);
        $profileUser = $stmt->fetch();

        if ($profileUser) {
            $html = view('user-profile.view.php', ['profileUser' => $profileUser]);
            $response->getBody()->write($html);
            return $response;
        } else {
            return $response->withStatus(404)->write('User not found');
        }
    }

}