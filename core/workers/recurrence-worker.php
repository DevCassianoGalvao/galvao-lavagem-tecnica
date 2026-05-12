<?php

require_once __DIR__ . '/../bootstrap.php';

$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);
$created = (new RecurrenceService($pdo, $logger))->scheduleMissingForCompletedServices(null, 100);

echo "Recorrencias criadas: {$created}" . PHP_EOL;
