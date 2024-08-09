<?php

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use PHPMailer\PHPMailer\PHPMailer;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
    'config' => function () {
        return require __DIR__ . '/../config.php';
    },
    PDO::class => function (ContainerInterface $c) {
        $config = $c->get('config');
        $pdo = new PDO($config['dsn'], $config['db_user'], $config['db_password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    },
    PHPMailer::class => function () {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'mailhog';
        $mail->SMTPAuth = false;
        $mail->Port = 1025;
        return $mail;
    },
    ]);
};
