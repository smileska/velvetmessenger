<?php
$config = require 'config.php';

try {
    $pdo = new PDO($config['dsn'], $config['db_user'], $config['db_password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
