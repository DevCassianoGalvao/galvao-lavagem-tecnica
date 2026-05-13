<?php
require_once __DIR__ . '/_bootstrap.php';
mvp_require_login();

$rows = mvp_service()->csvRows();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="contatos-galvao.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['nome', 'telefone', 'bairro', 'serviço', 'data'], ';');

foreach ($rows as $row) {
    fputcsv($out, $row, ';');
}

fclose($out);
