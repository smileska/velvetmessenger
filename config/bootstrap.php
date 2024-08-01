<?php

use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use App\Chatroom;
use App\Chat;

require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    'config' => $config,
    PDO::class => function (Container $c) {
        $config = $c->get('config');
        $pdo = new PDO($config['dsn'], $config['db_user'], $config['db_password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    },
    Chatroom::class => function (Container $c) {
        return new Chatroom($c->get(PDO::class));
    },
    Chat::class => function (Container $c) {
        return new Chat($c->get(PDO::class));
    },
]);

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

$dsn = $config['dsn'];
$dbUser = $config['db_user'];
$dbPassword = $config['db_password'];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $GLOBALS['pdo'] = $pdo;
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

$GLOBALS['pdo'] = $pdo;

require __DIR__ . '/../functions.php';

return $app;
