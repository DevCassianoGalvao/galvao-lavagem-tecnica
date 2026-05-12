<?php

require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);
SecurityService::requireJsonPost();
SecurityService::requireJsonCsrf($logger);
(new RateLimitService($pdo, $logger))->requireAllowed('tags', 40, 3600);

// Placeholder para CRUD real da tabela tags com PDO prepared statements.
echo json_encode([
    'success' => true,
    'message' => 'Tag preparada para persistencia.',
    'tag' => [
        'name' => clean_text($_POST['tag_name'] ?? ''),
        'color' => clean_text($_POST['tag_color'] ?? '#C8A95B'),
    ],
]);
