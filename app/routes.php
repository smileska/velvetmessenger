<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

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

$app->post('/register', function (Request $request, Response $response, $args) use ($pdo) {
    $parsedBody = $request->getParsedBody();
    $uploadedFiles = $request->getUploadedFiles();

    $username = $parsedBody['username'] ?? null;
    $password = $parsedBody['password'] ?? null;
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

    $violations = validateUserData($username, $email, $password);
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

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, image, status) VALUES (:username, :password, :email, :image, :status)");
        $stmt->execute([
            'username' => $username,
            'password' => $hashedPassword,
            'email' => $email,
            'image' => $imagePath,
            'status' => 'Online'
        ]);
        $_SESSION['username'] = $username;
        $_SESSION['image'] = $imagePath;
        $html = view("index.view.php", ['username' => $username]);
        $response->getBody()->write($html);
        return $response->withStatus(201);
    } catch (PDOException $e) {
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
    $html = view('index.view.php');
    $username = $parsedBody['username'] ?? null;
    $password = $parsedBody['password'] ?? null;
    if ($username === null || $password === null) {
        $errors = "Please enter username and password";
        $html = view('login.view.php', ['errors' => $errors]);
        $response->getBody()->write($html);
        return $response->withStatus(400);
    }
    $_SESSION['username'] = $username;
//    $violations = validateUserData($username, '', $password);
//
//    if (count($violations) > 0) {
//        $errors = [];
//        foreach ($violations as $violation) {
//            $errors[] = $violation->getMessage();
//        }
//        $html = view('login.view.php', ['errors' => $errors]);
//        $response->getBody()->write($html);
//        return $response->withStatus(400);
//    }
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
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

$app->post('/send-message', function (Request $request, Response $response, $args) use ($pdo) {
    if (!isset($_SESSION['username'])) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $parsedBody = $request->getParsedBody();
    $sender = $_SESSION['username'];
    $recipient = $parsedBody['recipient'] ?? '';
    $message = $parsedBody['message'] ?? '';

    if (empty($recipient)) {
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




