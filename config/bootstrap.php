<?php

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config.php';

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
