<?php
require_once __DIR__ . '/../_bootstrap.php';
mvp_require_login();

$path = mvp_service()->imagePath((int) ($_GET['id'] ?? 0));

if (!$path) {
    http_response_code(404);
    exit;
}

$mime = mime_content_type($path) ?: 'image/jpeg';

if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    http_response_code(403);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
readfile($path);
