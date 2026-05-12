<?php

require_once __DIR__ . '/../core/bootstrap.php';

$pdo = Connection::get($config);
$auth = new AuthService($pdo, new SecurityLogger($pdo), new RateLimitService($pdo));
$auth->logout();

header('Location: /admin/login.php');
exit;
