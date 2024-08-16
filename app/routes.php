<?php

use Controllers\PageController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use Slim\App;
use Controllers\UserController;
use Controllers\AuthController;
use Controllers\ChatController;
use Controllers\ChatroomController;
use Controllers\ProfileController;
use Middleware\SessionMiddleware;

require __DIR__ . '/../src/Validator/validator.php';

return function (App $app) {
    $container = $app->getContainer();

    $app->get('/', function (Request $request, Response $response) use ($container) {
        return $container->get(PageController::class)->home($request, $response);
    });
    $app->get('/notes', function (Request $request, Response $response) use ($container) {
        return $container->get(PageController::class)->notes($request, $response);
    });
    $app->get('/register', function (Request $request, Response $response) use ($container) {
        return $container->get(UserController::class)->showRegister($request, $response);
    });
    $app->post('/register', function (Request $request, Response $response) use ($container) {
        return $container->get(UserController::class)->register($request, $response);
    });
    $app->post('/login', function (Request $request, Response $response) use ($container) {
        return $container->get(AuthController::class)->login($request, $response);
    });
    $app->get('/logout', function (Request $request, Response $response) use ($container) {
        return $container->get(AuthController::class)->logout($request, $response);
    });
    $app->post('/verify-email', function (Request $request, Response $response) use ($container) {
        return $container->get(AuthController::class)->verifyEmail($request, $response);
    });
    $app->get('/login', function (Request $request, Response $response) use ($container) {
        return $container->get(AuthController::class)->showLogin($request, $response);
    });
    $app->post('/search', function (Request $request, Response $response) use ($container) {
        return $container->get(UserController::class)->search($request, $response);
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

    $app->group('', function (\Slim\Routing\RouteCollectorProxy $group) {
        $group->get('/profile', ProfileController::class . ':showProfile');
        $group->post('/update-username', ProfileController::class . ':updateUsername');
        $group->post('/update-password', ProfileController::class . ':updatePassword');
        $group->post('/toggle-dark-mode', UserController::class . ':toggleDarkMode');

        $group->get('/get-previous-messages', ChatController::class . ':getPreviousMessages');
        $group->get('/get-messages/{recipient}', ChatController::class . ':getMessages');
        $group->post('/send-message', ChatController::class . ':sendMessage');
        $group->post('/speech-to-text', ChatController::class . ':handleSpeechToText');
        $group->post('/message/{id}/react', ChatController::class . ':reactToMessage');

        $group->get('/get-chatrooms', ChatroomController::class . ':getChatrooms');
        $group->get('/chatroom/{id}', ChatroomController::class . ':showChatroom');
        $group->get('/chatroom/{id}/messages', ChatroomController::class . ':getMessages');
        $group->post('/create-chatroom', ChatroomController::class . ':createChatroom');
        $group->post('/chatroom/{id}/add-user', ChatroomController::class . ':addUser');
        $group->post('/chatroom/{id}/suggest-user', ChatroomController::class . ':suggestUser');
        $group->get('/chatroom/{id}/suggested-users', ChatroomController::class . ':getSuggestedUsers');
        $group->post('/chatroom/{id}/approve-suggestion', ChatroomController::class . ':approveSuggestion');
        $group->post('/chatroom/{id}/delete-suggestion', ChatroomController::class . ':deleteSuggestion');
        $group->get('/chatroom/{id}/users', ChatroomController::class . ':getUsers');
        $group->post('/chatroom/{id}/remove-user', ChatroomController::class . ':removeUser');
        $group->post('/chatroom/{id}/leave', ChatroomController::class . ':leaveRoom');
        $group->post('/chatroom/{id}/grant-admin', ChatroomController::class . ':grantAdmin');
        $group->get('/chatroom/{id}/is-admin', ChatroomController::class . ':isAdmin');
        $group->post('/chatroom-message/{id}/react', ChatroomController::class . ':reactToChatroomMessage');
        $group->get('/chatroom/{id}/user-role', ChatroomController::class . ':getUserRole');
    })->add(new SessionMiddleware());

    $app->get('/{username}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(UserController::class)->showUserProfile($request, $response, $args);
    });
    $app->get('/chat/{username}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatController::class)->showChat($request, $response, $args);
    });
};
