<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use App\Chat;

$config = require __DIR__ . '/config.php';

try {
    $pdo = new PDO($config['dsn'], $config['db_user'], $config['db_password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage() . ' (Error Code: ' . $e->getCode() . ')');
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