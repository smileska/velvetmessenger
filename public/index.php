<?php
//require '../functions.php';
session_start();
//dd($_SESSION);
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../app/routes.php';


$app->run();