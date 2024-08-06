<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Chatroom;
use Slim\App;
use DI\Container;

require __DIR__ . '/../src/Validator/validator.php';
//global $pdo;

return function (App $app) {
    $container = $app->getContainer();

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
    $app->get('/get-previous-messages', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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
    });

    $app->get('/get-messages/{recipient}', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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
    });

    $app->post('/register', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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
                $mail->Host = 'mailhog';                             // Set the SMTP server to send through
                $mail->SMTPAuth = false;                                 // No SMTP authentication
                $mail->Port = 1025;                                  // TCP port to connect to

                // recipients
                $mail->setFrom('velvetmessenger@example.com', 'Velvet Messenger');
                $mail->addAddress($email);

                // content
                $mail->isHTML(true);                                       // HTML format for the email
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
    });


    $app->post('/verify-email', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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


    $app->post('/login', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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
            $_SESSION['user_id'] = $user['id'];
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

    $app->get('/profile', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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
    $app->post('/update-profile-picture', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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


    $app->post('/search', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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

    $app->post('/send-message', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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
    });


    $app->post('/create-chatroom', function (Request $request, Response $response) use ($container) {
        $chatroom = $container->get(Chatroom::class);
        $data = $request->getParsedBody();

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

    $app->get('/get-chatrooms', function (Request $request, Response $response) use ($container) {
        $chatroom = $container->get(Chatroom::class);
        if (!isset($_SESSION['user_id'])) {
            $response->getBody()->write('User not authenticated');
            return $response->withStatus(401);
        }
        $userId = (int)$_SESSION['user_id'];
        $chatrooms = $chatroom->getUserChatrooms($userId);
        $response->getBody()->write(json_encode($chatrooms));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/chatroom/{id}', function (Request $request, Response $response, $args) use ($container) {
        $chatroom = $container->get(Chatroom::class);
        if (!isset($_SESSION['user_id'])) {
            $response->getBody()->write(json_encode(['message' => 'User not authenticated']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $chatroomId = (int)$args['id'];
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

    $app->get('/{username}', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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

    $app->get('/chat/{username}', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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

    $app->get('/chatroom/{id}/user-role', function (Request $request, Response $response, $args) use ($container) {
        $chatroom = $container->get(Chatroom::class);
        $chatroomId = (int)$args['id'];
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $data = ['role' => 'guest'];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $isAdmin = $chatroom->isAdmin($chatroomId, $userId);
        $role = $isAdmin ? 'admin' : 'user';

        $data = ['role' => $role];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    });


    $app->post('/chatroom/{id}/add-user', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
        $chatroom = $container->get(Chatroom::class);
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
                throw new Exception("Only admins can add users to the chatroom");
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

            $result = $chatroom->addUser($chatroomId, $userId);

            if (!$result) {
                throw new Exception("Failed to add user: $username to chatroom: $chatroomId");
            }

            $response->getBody()->write(json_encode(['message' => 'User added successfully']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $response->getBody()->write(json_encode([
                'message' => $e->getMessage(),
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });

    $app->post('/chatroom/{id}/suggest-user', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
        $chatroomId = (int)$args['id'];
        $data = $request->getParsedBody();
        $username = $data['username'] ?? null;

        $currentUserId = $_SESSION['user_id'] ?? null;

        if (!$currentUserId) {
            $response->getBody()->write(json_encode(['message' => 'User not authenticated']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            $chatroom = $container->get(Chatroom::class);
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
    });

    $app->get('/chatroom/{id}/suggested-users', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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
    });

    $app->post('/chatroom/{id}/approve-suggestion', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
        $chatroomId = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $data['user_id'] ?? null;

        $currentUserId = $_SESSION['user_id'] ?? null;

        if (!$currentUserId) {
            $response->getBody()->write(json_encode(['message' => 'User not authenticated']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            $chatroom = $container->get(Chatroom::class);
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
    });

    $app->post('/chatroom/{id}/delete-suggestion', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
        $chatroomId = (int)$args['id'];
        $data = $request->getParsedBody();
        $userId = $data['user_id'] ?? null;

        $currentUserId = $_SESSION['user_id'] ?? null;

        if (!$currentUserId) {
            $response->getBody()->write(json_encode(['message' => 'User not authenticated']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            $chatroom = $container->get(Chatroom::class);
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
    });

    $app->get('/chatroom/{id}/users', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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
    });


    $app->post('/chatroom/{id}/remove-user', function (Request $request, Response $response, $args) use ($container) {
        $chatroom = $container->get(Chatroom::class);
        $pdo = $container->get(PDO::class);
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
    });

    $app->post('/chatroom/{id}/leave', function (Request $request, Response $response, $args) use ($container) {
        $chatroom = $container->get(Chatroom::class);
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
    });

    $app->get('/chatroom/{id}/messages', function (Request $request, Response $response, $args) use ($container) {
        $chatroom = $container->get(Chatroom::class);
        $chatroomId = $args['id'];
        $messages = $chatroom->getMessages($chatroomId);

        $response->getBody()->write(json_encode($messages));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });

    $app->get('/chatroom/{id}/is-admin', function (Request $request, Response $response, $args) use ($container) {
        $chatroom = $container->get(Chatroom::class);
        $chatroomId = $args['id'];
        $userId = $_SESSION['user_id'];

        $isAdmin = $chatroom->isAdmin($chatroomId, $userId);

        $response->getBody()->write(json_encode(['isAdmin' => $isAdmin]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->post('/update-username', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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

    $app->post('/update-password', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
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

    $app->post('/chatroom/{id}/grant-admin', function (Request $request, Response $response, $args) use ($container) {
        $pdo = $container->get(PDO::class);
        $chatroom = $container->get(Chatroom::class);
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
    });
    $app->post('/toggle-dark-mode', function (Request $request, Response $response) {
        $darkMode = $_SESSION['dark_mode'] ?? false;
        $_SESSION['dark_mode'] = !$darkMode;

        $data = ['dark_mode' => $_SESSION['dark_mode']];
        $response->getBody()->write(json_encode($data));

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->post('/message/{id}/react', function (Request $request, Response $response, $args) use ($container) {
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

        $pdo = $container->get(PDO::class);

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
    });


    $app->post('/chatroom-message/{id}/react', function (Request $request, Response $response, $args) use ($container) {
        $messageId = $args['id'];
        $userId = $_SESSION['user_id'];
        $reactionType = $request->getParsedBody()['reactionType'];

        $pdo = $container->get(PDO::class);

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
    });


};