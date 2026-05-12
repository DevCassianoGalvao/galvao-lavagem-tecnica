<?php

require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metodo nao permitido.',
    ]);
    exit;
}

if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
    http_response_code(419);
    echo json_encode([
        'success' => false,
        'message' => 'Token de seguranca invalido.',
    ]);
    exit;
}

$payload = [
    'name' => clean_text($_POST['name'] ?? ''),
    'phone' => clean_text($_POST['phone'] ?? ''),
    'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
    'address' => clean_text($_POST['address'] ?? ''),
    'latitude' => clean_text($_POST['latitude'] ?? ''),
    'longitude' => clean_text($_POST['longitude'] ?? ''),
    'property_type' => clean_text($_POST['property_type'] ?? ''),
    'surfaces' => array_map('clean_text', $_POST['surfaces'] ?? []),
    'dirt_types' => array_map('clean_text', $_POST['dirt_types'] ?? []),
    'area_size' => clean_text($_POST['area_size'] ?? ''),
    'square_meters' => clean_text($_POST['square_meters'] ?? ''),
    'access_difficulty' => clean_text($_POST['access_difficulty'] ?? ''),
    'elevated_height' => clean_text($_POST['elevated_height'] ?? ''),
    'cleaning_frequency' => clean_text($_POST['cleaning_frequency'] ?? ''),
    'priority' => clean_text($_POST['priority'] ?? ''),
    'notes' => clean_text($_POST['notes'] ?? ''),
];

$imageCount = isset($_FILES['images']['name']) && is_array($_FILES['images']['name'])
    ? count(array_filter($_FILES['images']['name']))
    : 0;

if ($payload['name'] === '' || $payload['phone'] === '' || $payload['email'] === '') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Dados obrigatorios ausentes.',
    ]);
    exit;
}

if ($imageCount > 10) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Envie no maximo 10 imagens.',
    ]);
    exit;
}

// Placeholder de persistencia: proxima etapa pode salvar em MySQL e mover imagens para storage/uploads/quiz.
echo json_encode([
    'success' => true,
    'message' => 'Diagnostico tecnico recebido.',
    'diagnostic' => $payload,
    'images_received' => $imageCount,
]);
