<?php

require_once __DIR__ . '/../../core/bootstrap.php';
require_auth();

$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);
(new RateLimitService($pdo, $logger))->requireAllowed('visual_bank_image', 240, 3600);

$uploadId = (int) ($_GET['id'] ?? 0);
$size = ($_GET['size'] ?? '') === 'thumb' ? 'thumb' : 'original';

if ($uploadId <= 0) {
    http_response_code(404);
    exit;
}

$upload = (new VisualBankService($pdo))->uploadPath($uploadId, $size);

if (!$upload) {
    http_response_code(404);
    exit;
}

$path = realpath(STORAGE_PATH . '/' . ltrim((string) $upload['storage_path'], '/'));
$storageRoot = realpath(STORAGE_PATH);

if (!$path || !$storageRoot || !str_starts_with($path, $storageRoot) || !is_file($path)) {
    $logger->log('warning', 'visual_image_missing', 'Imagem solicitada nao encontrada.', ['upload_id' => $uploadId]);
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $upload['mime_type']);
header('Cache-Control: private, max-age=604800, stale-while-revalidate=86400');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($path));

$lastModified = gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT';
$etag = '"' . sha1($path . '|' . filesize($path) . '|' . filemtime($path)) . '"';
header('Last-Modified: ' . $lastModified);
header('ETag: ' . $etag);

if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag || ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '') === $lastModified) {
    http_response_code(304);
    exit;
}

readfile($path);
