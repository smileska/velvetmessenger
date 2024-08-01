<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use App\Chat;
use DI\ContainerBuilder;

$config = require __DIR__ . '/config.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    PDO::class => function () use ($config) {
        $pdo = new PDO($config['dsn'], $config['db_user'], $config['db_password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
]);

try {
    $container = $containerBuilder->build();
    $pdo = $container->get(PDO::class);
} catch (Exception $e) {
    die('Error setting up the container: ' . $e->getMessage());
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat($pdo)
        )
    ),
    8080,
    '0.0.0.0'
);

echo "WebSocket server started on 0.0.0.0:8080\n";

$server->run();
