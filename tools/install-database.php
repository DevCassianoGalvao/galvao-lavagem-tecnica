<?php

require_once __DIR__ . '/../core/config/app.php';

$host = (string) ($config['db_host'] ?? '127.0.0.1');
$charset = (string) ($config['db_charset'] ?? 'utf8mb4');
$user = (string) ($config['db_user'] ?? 'root');
$password = (string) ($config['db_pass'] ?? '');
$schemaFile = BASE_PATH . '/core/database/schema.sql';
$adminEmail = 'admin@galvao.local';
$adminPassword = 'Admin@12345';

if (!is_file($schemaFile)) {
    fwrite(STDERR, "schema.sql nao encontrado.\n");
    exit(1);
}

$pdo = new PDO(
    sprintf('mysql:host=%s;charset=%s', $host, $charset),
    $user,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$sql = file_get_contents($schemaFile) ?: '';
$sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if ($statement === '') {
        continue;
    }

    $pdo->exec($statement);
}

$pdo->exec('USE galvao_lavagem_tecnica');
$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, phone, password_hash, role, status, created_at, updated_at)
     VALUES (:name, :email, "", :password_hash, "owner", "active", NOW(), NOW())
     ON DUPLICATE KEY UPDATE
       name = VALUES(name),
       password_hash = VALUES(password_hash),
       role = VALUES(role),
       status = VALUES(status),
       updated_at = NOW()'
);
$stmt->execute([
    'name' => 'Administrador Galvao',
    'email' => $adminEmail,
    'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
]);

echo "Banco instalado com sucesso.\n";
echo "Database: galvao_lavagem_tecnica\n";
echo "Admin: {$adminEmail}\n";
echo "Senha: {$adminPassword}\n";
