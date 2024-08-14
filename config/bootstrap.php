<?php

use App\Chatroom;
use Controllers\UserController;
use Controllers\AuthController;
use Controllers\ChatController;
use Controllers\ChatroomController;
use Controllers\ProfileController;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config.php';

$containerBuilder = new ContainerBuilder();
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);

try {
    $container = $containerBuilder->build();
} catch (Exception $e) {
    die('Container building failed: ' . $e->getMessage());
}

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(
    $config['settings']['displayErrorDetails'],
    $config['settings']['logErrors'],
    $config['settings']['logErrorDetails']
);

$container->set(UserController::class, function (ContainerInterface $container) {
    return new UserController($container->get(PDO::class));
});

$container->set(AuthController::class, function (ContainerInterface $container) {
    return new AuthController($container->get(PDO::class));
});

$container->set(ChatController::class, function (ContainerInterface $container) {
    return new ChatController($container->get(PDO::class));
});

$container->set(Chatroom::class, function (ContainerInterface $container) {
    return new Chatroom($container->get(PDO::class));
});

$container->set(ChatroomController::class, function (ContainerInterface $container) {
    return new ChatroomController($container->get(PDO::class), $container->get(Chatroom::class));
});

$container->set(ProfileController::class, function (ContainerInterface $container) {
    return new ProfileController($container->get(PDO::class));
});

require __DIR__ . '/../functions.php';

return $app;