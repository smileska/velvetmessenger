<?php

use Controllers\PageController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Chatroom;
use Slim\App;
use DI\Container;
use Controllers\UserController;
use Controllers\AuthController;
use Controllers\ChatController;
use Controllers\ChatroomController;
use Controllers\ProfileController;

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
    $app->get('/get-previous-messages', function (Request $request, Response $response) use ($container) {
        return $container->get(ChatController::class)->getPreviousMessages($request, $response);
    });
    $app->get('/get-messages/{recipient}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatController::class)->getMessages($request, $response, $args);
    });
    $app->post('/register', function (Request $request, Response $response) use ($container) {
        $userController = $container->get(UserController::class);
        return $userController->register($request, $response);
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
    $app->get('/profile', function (Request $request, Response $response) use ($container) {
        return $container->get(ProfileController::class)->showProfile($request, $response);
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
    $app->post('/search', function (Request $request, Response $response) use ($container) {
        return $container->get(UserController::class)->search($request, $response);
    });
    $app->post('/send-message', function (Request $request, Response $response) use ($container) {
        return $container->get(ChatController::class)->sendMessage($request, $response);
    });
    $app->post('/create-chatroom', function (Request $request, Response $response) use ($container) {
        return $container->get(ChatroomController::class)->createChatroom($request, $response);
    });
    $app->get('/get-chatrooms', function (Request $request, Response $response) use ($container) {
        return $container->get(ChatroomController::class)->getChatrooms($request, $response);
    });
    $app->get('/chatroom/{id}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatroomController::class)->showChatroom($request, $response, $args);
    });
    $app->get('/{username}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(UserController::class)->showUserProfile($request, $response, $args);
    });
    $app->get('/chat/{username}', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatController::class)->showChat($request, $response, $args);
    });
    $app->get('/chatroom/{id}/user-role', function (Request $request, Response $response, $args) use ($container){
        return $container->get(ChatroomController::class)->getUserRole($request, $response, $args);
    });
    $app->post('/chatroom/{id}/add-user', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatroomController::class)->addUser($request, $response, $args);
    });
    $app->post('/chatroom/{id}/suggest-user', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatroomController::class)->suggestUser($request, $response, $args);
    });
    $app->get('/chatroom/{id}/suggested-users', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatroomController::class)->getSuggestedUsers($request, $response, $args);
    });
    $app->post('/chatroom/{id}/approve-suggestion', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatroomController::class)->approveSuggestion($request, $response, $args);
    });
    $app->post('/chatroom/{id}/delete-suggestion', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatroomController::class)->deleteSuggestion($request, $response, $args);
    });
    $app->get('/chatroom/{id}/users', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatroomController::class)->getUsers($request, $response, $args);
    });
    $app->post('/chatroom/{id}/remove-user', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatroomController::class)->removeUser($request, $response, $args);
    });
    $app->post('/chatroom/{id}/leave', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatroomController::class)->leaveRoom($request, $response, $args);
    });
    $app->get('/chatroom/{id}/messages', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatroomController::class)->getMessages($request, $response, $args);
    });
    $app->get('/chatroom/{id}/is-admin', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatroomController::class)->isAdmin($request, $response, $args);
    });
    $app->post('/update-username', function (Request $request, Response $response) use ($container) {
        return $container->get(ProfileController::class)->updateUsername($request, $response);
    });
    $app->post('/chatroom/{id}/grant-admin', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatroomController::class)->grantAdmin($request, $response, $args);
    });
    $app->post('/update-password', function (Request $request, Response $response) use ($container) {
        return $container->get(ProfileController::class)->updatePassword($request, $response);
    });
    $app->post('/toggle-dark-mode', function (Request $request, Response $response) use ($container) {
        return $container->get(UserController::class)->toggleDarkMode($request, $response);
    });
    $app->post('/message/{id}/react', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatController::class)->reactToMessage($request, $response, $args);
    });
    $app->post('/chatroom-message/{id}/react', function (Request $request, Response $response, $args) use ($container) {
        return $container->get(ChatroomController::class)->reactToChatroomMessage($request, $response, $args);
    });

    function updateUserStatus($pdo, $username, $status)
    {
        $stmt = $pdo->prepare('UPDATE users SET status = :status WHERE username = :username');
        $stmt->execute(['status' => $status, 'username' => $username]);
    }

    $app->post('/speech-to-text', function (Request $request, Response $response) use ($container) {
        return $container->get(ChatController::class)->handleSpeechToText($request, $response);
    });


};