<?php

require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'success' => true,
    'score' => 0,
    'message' => 'Endpoint placeholder para scoring de leads.',
]);
