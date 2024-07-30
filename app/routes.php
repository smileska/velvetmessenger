<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Chatroom;

require __DIR__ . '/../src/Validator/validator.php';
global $pdo;


$app->get('/', function (Request $request, Response $response, $args) {
    $html = view('index.view.php');
    $response->getBody()->write($html);
    return $response;
});
$app->get('/notes', function (Request $request, Response $response, $args) {
    $html = view('index.view.php');
    $response->getBody()->write($html);
    return $response;
});

$app->get('/register', function (Request $request, Response $response, $args) {
    $html = view('register.view.php');
    $response->getBody()->write($html);
    return $response;
});
$app->get('/get-previous-messages', function (Request $request, Response $response, $args) use ($pdo) {
    $sender = $request->getQueryParams()['sender'] ?? '';
    $recipient = $request->getQueryParams()['recipient'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM messages WHERE (sender = :sender AND recipient = :recipient) OR (sender = :recipient AND recipient = :sender) ORDER BY timestamp ASC');
    $stmt->execute(['sender' => $sender, 'recipient' => $recipient]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $response->withJson($messages);
});
$app->get('/get-messages/{recipient}', function (Request $request, Response $response, $args) use ($pdo) {
    $sender = $_SESSION['username'];
    $recipient = $args['recipient'];

    $stmt = $pdo->prepare('SELECT * FROM messages WHERE (sender = :sender AND recipient = :recipient) OR (sender = :recipient AND recipient = :sender) ORDER BY timestamp ASC');
    $stmt->execute(['sender' => $sender, 'recipient' => $recipient]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($messages));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/register', function (Request $request, Response $response, $args) use ($pdo) {
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
        $stmt = $pdo->prepare("INSERT INTO unverified_users (username, password, email, image, verification_code) VALUES (:username, :password, :email, :image, :verification_code)");
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
            $mail->isSMTP();                                           // Send using SMTP
            $mail->Host       = 'mailhog';                             // Set the SMTP server to send through
            $mail->SMTPAuth   = false;                                 // No SMTP authentication
            $mail->Port       = 1025;                                  // TCP port to connect to

            // recipients
            $mail->setFrom('velvetmessenger@example.com', 'Velvet Messenger');
            $mail->addAddress($email);

            // content
            $mail->isHTML(true);                                       // HTML format for the email
            $mail->Subject = 'Email Verification';
            $mail->Body    = 'Your verification code is: ' . $verificationCode;

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
});


$app->post('/verify-email', function (Request $request, Response $response, $args) use ($pdo) {
    $parsedBody = $request->getParsedBody();
    $verificationCode = $parsedBody['verification_code'] ?? null;
    $email = $parsedBody['email'] ?? null;

    if (!$verificationCode || !$email) {
        $html = view('verify.view.php', ['errors' => ['Verification code or email is missing.'], 'email' => $email]);
        $response->getBody()->write($html);
        return $response->withStatus(400);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT * FROM unverified_users WHERE email = :email AND verification_code = :verification_code');
        $stmt->execute(['email' => $email, 'verification_code' => $verificationCode]);
        $user = $stmt->fetch();

        if ($user) {
            $stmt = $pdo->prepare('INSERT INTO users (username, password, email, image, status) VALUES (:username, :password, :email, :image, :status)');
            $stmt->execute([
                'username' => $user['username'],
                'password' => $user['password'],
                'email' => $user['email'],
                'image' => $user['image'],
                'status' => 'Online'
            ]);

            $stmt = $pdo->prepare('DELETE FROM unverified_users WHERE email = :email');
            $stmt->execute(['email' => $email]);

            $pdo->commit();

            $html = view('email-verified.view.php');
            $response->getBody()->write($html);
            return $response->withStatus(200);
        } else {
            $pdo->rollBack();
            $html = view('verify.view.php', ['errors' => ['Invalid verification code.'], 'email' => $email]);
            $response->getBody()->write($html);
            return $response->withStatus(400);
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $response->getBody()->write("Error: " . $e->getMessage());
        return $response->withStatus(500);
    }
});

$app->get('/login', function (Request $request, Response $response, $args) {
    $html = view('login.view.php');
    $response->getBody()->write($html);
    return $response;
});


$app->post('/login', function (Request $request, Response $response, $args) use ($pdo) {
    $parsedBody = $request->getParsedBody();
    $username = $parsedBody['username'] ?? null;
    $password = $parsedBody['password'] ?? null;

    if ($username === null || $password === null) {
        $errors = "Please enter username and password";
        $html = view('login.view.php', ['errors' => $errors]);
        $response->getBody()->write($html);
        return $response->withStatus(400);
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];  // Add this line
        $_SESSION['username'] = $username;
        $_SESSION['image'] = $user['image'];
        $stmt = $pdo->prepare('UPDATE users SET status = \'Online\' WHERE username = :username');
        $stmt->execute(['username' => $username]);
        return $response->withHeader('Location', '/')->withStatus(302);
    } else {
        $errors = "Username or password is incorrect";
        $html = view('login.view.php', ['errors' => $errors]);
        $response->getBody()->write($html);
        return $response->withStatus(401);
    }
});

$app->get('/logout', function (Request $request, Response $response, $args) {
//    if (isset($_SESSION['username'])) {
//        $username = $_SESSION['username'];
//        $stmt = $pdo->prepare('UPDATE users SET status = "Offline" WHERE username = :username');
//        $stmt->execute(['username' => $username]);
//    }
    logout();
    $html = view('index.view.php');
    $response->getBody()->write($html);
    return $response->withStatus(200);
});

$app->add(function (Request $request, $handler) {
    $uri = $request->getUri()->getPath();
    if (preg_match('/^\/ui\/icons\/.+\.(png|jpg|jpeg|gif|css|js)$/', $uri)) {
        $file = __DIR__ . '/../public' . $uri;
        if (file_exists($file)) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(file_get_contents($file));
            return $response->withHeader('Content-Type', mime_content_type($file));
        }
    }
    return $handler->handle($request);
});

$app->get('/profile', function (Request $request, Response $response, $args) use ($pdo) {
    if (!isset($_SESSION['username'])) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $username = $_SESSION['username'];
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user) {
        $html = view('profile.view.php', ['user' => $user]);
        $response->getBody()->write($html);
        return $response;
    } else {
        return $response->withStatus(404)->write('User not found');
    }
});
$app->post('/update-profile-picture', function (Request $request, Response $response, $args) use ($pdo) {
    if (!isset($_SESSION['username'])) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

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
});


$app->post('/search', function (Request $request, Response $response, $args) use ($pdo) {
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
});

$app->post('/send-message', function (Request $request, Response $response, $args) use ($pdo) {
    if (!isset($_SESSION['username'])) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $parsedBody = $request->getParsedBody();
    $sender = $_SESSION['username'];
    $recipient = $parsedBody['recipient'] ?? '';
    $message = $parsedBody['message'] ?? '';

    if (empty($recipient) || empty($message)) {
        $response->getBody()->write('Recipient and message are required.');
        return $response->withStatus(400);
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO messages (sender, recipient, message) VALUES (:sender, :recipient, :message)');
        $stmt->execute(['sender' => $sender, 'recipient' => $recipient, 'message' => $message]);
        return $response->withHeader('Location', '/chat/' . $recipient)->withStatus(302);
    } catch (PDOException $e) {
        $response->getBody()->write("Error: " . $e->getMessage());
        return $response->withStatus(500);
    }
});


$app->post('/create-chatroom', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();
    $chatroom = new Chatroom($pdo);

//    if (!isset($_SESSION['user_id'])) {
//        $response->getBody()->write('User not authenticated');
//        return $response->withStatus(401);
//    }

    $ownerId = (int)$_SESSION['user_id'];

    if ($ownerId === 0) {
        $response->getBody()->write('Invalid user ID');
        return $response->withStatus(400);
    }

    try {
        $chatroomId = $chatroom->create($data['name'], $ownerId);
        return $response->withHeader('Location', '/')->withStatus(302);
    } catch (PDOException $e) {
        error_log('Error creating chatroom: ' . $e->getMessage());
        $response->getBody()->write('Error creating chatroom');
        return $response->withStatus(500);
    }
});

$app->get('/get-chatrooms', function (Request $request, Response $response) use ($pdo) {
    if (!isset($_SESSION['user_id'])) {
        $response->getBody()->write('User not authenticated');
        return $response->withStatus(401);
    }

    $chatroom = new Chatroom($pdo);
    $userId = (int)$_SESSION['user_id'];
    $chatrooms = $chatroom->getUserChatrooms($userId);
    $response->getBody()->write(json_encode($chatrooms));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/chatroom/{id}', function (Request $request, Response $response, $args) use ($pdo) {
    if (!isset($_SESSION['user_id'])) {
        $response->getBody()->write(json_encode(['message' => 'User not authenticated']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }

    $chatroomId = (int)$args['id'];
    $chatroom = new Chatroom($pdo);
    $chatroomData = $chatroom->getChatroomData($chatroomId);

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
});

$app->get('/{username}', function (Request $request, Response $response, $args) use ($pdo) {
    $username = $args['username'];
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    if ($user) {
        $html = view('user-profile.view.php', ['user' => $user]);
        $response->getBody()->write($html);
        return $response;
    } else {
        return $response->withStatus(404)->write('User not found');
    }
});

$app->get('/chat/{username}', function (Request $request, Response $response, $args) use ($pdo) {
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
});

$app->post('/chatroom/{id}/add-user', function (Request $request, Response $response, $args) use ($pdo) {
    $chatroomId = $args['id'];
    $data = $request->getParsedBody();
    $username = $data['username'];

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'User not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $chatroom = new Chatroom($pdo);
    $result = $chatroom->addUser($chatroomId, $userId);

    if ($result) {
        $response->getBody()->write(json_encode(['success' => true, 'message' => 'User added successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to add user or user already in chatroom']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

$app->post('/chatroom/{id}/remove-user', function (Request $request, Response $response, $args) use ($pdo) {
    $chatroomId = $args['id'];
    $data = $request->getParsedBody();
    $username = $data['username'];

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        $response->getBody()->write(json_encode(['message' => 'User not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $chatroom = new Chatroom($pdo);
    $result = $chatroom->removeUser($chatroomId, $userId);

    if ($result) {
        $response->getBody()->write(json_encode(['message' => 'User removed successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(['message' => 'Failed to remove user']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

$app->post('/chatroom/{id}/leave', function (Request $request, Response $response, $args) use ($pdo) {
    $chatroomId = $args['id'];
    $userId = $_SESSION['user_id'];

    $chatroom = new Chatroom($pdo);
    $result = $chatroom->removeUser($chatroomId, $userId);

    if ($result) {
        $response->getBody()->write(json_encode(['message' => 'Left chatroom successfully']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(['message' => 'Failed to leave chatroom']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

$app->get('/chatroom/{id}/messages', function (Request $request, Response $response, $args) use ($pdo) {
    $chatroomId = $args['id'];
    $chatroom = new Chatroom($pdo);
    $messages = $chatroom->getMessages($chatroomId);

    $response->getBody()->write(json_encode($messages));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->post('/update-username', function (Request $request, Response $response, $args) use ($pdo) {
    $parsedBody = $request->getParsedBody();
    $newUsername = $parsedBody['new_username'] ?? '';
    $password = $parsedBody['password_for_username'] ?? '';

    if (!isset($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $html = view('profile.view.php', ['error' => 'Incorrect password']);
        $response->getBody()->write($html);
        return $response->withStatus(400);
    }

    $stmt = $pdo->prepare('UPDATE users SET username = :username WHERE id = :id');
    $stmt->execute(['username' => $newUsername, 'id' => $userId]);

    $_SESSION['username'] = $newUsername;

    return $response->withHeader('Location', '/profile')->withStatus(302);
});

$app->post('/update-password', function (Request $request, Response $response, $args) use ($pdo) {
    $parsedBody = $request->getParsedBody();
    $currentPassword = $parsedBody['current_password'] ?? '';
    $newPassword = $parsedBody['new_password'] ?? '';
    $confirmNewPassword = $parsedBody['confirm_new_password'] ?? '';

    if (!isset($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

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
    $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
    $stmt->execute(['password' => $hashedNewPassword, 'id' => $userId]);

    return $response->withHeader('Location', '/profile')->withStatus(302);
});
