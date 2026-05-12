<?php

require_once __DIR__ . '/../bootstrap.php';

$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);
$created = (new NotificationService($pdo, $logger))->generateOperationalAlerts();

echo "Notificacoes criadas: {$created}" . PHP_EOL;
