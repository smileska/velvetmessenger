<?php
session_start();

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../config/bootstrap.php';
$routes = require __DIR__ . '/../app/routes.php';

$routes($app);

$app->run();