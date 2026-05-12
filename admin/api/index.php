<?php

require_once __DIR__ . '/../../core/bootstrap.php';

$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);
$request = ApiRequest::capture();

(new ApiRouter($pdo, $config, $logger))->dispatch($request);
