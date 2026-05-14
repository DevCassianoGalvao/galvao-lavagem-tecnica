<?php

declare(strict_types=1);

$config = require __DIR__ . '/../core/config/app.php';

require_once __DIR__ . '/../core/helpers/sanitize.php';
require_once __DIR__ . '/../core/database/Connection.php';
require_once __DIR__ . '/../core/security/SessionService.php';
require_once __DIR__ . '/../core/security/CsrfService.php';
require_once __DIR__ . '/../core/security/csrf.php';
require_once __DIR__ . '/../core/security/SecurityLogger.php';
require_once __DIR__ . '/../core/security/RateLimitService.php';
require_once __DIR__ . '/../core/security/UploadSecurityService.php';
require_once __DIR__ . '/../core/services/MvpService.php';
require_once __DIR__ . '/../core/services/LeadNotificationService.php';

date_default_timezone_set((string) ($config['app_timezone'] ?? 'America/Sao_Paulo'));

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

SessionService::start();

function mvp_pdo(): PDO
{
    global $config;
    return Connection::get($config);
}

function mvp_service(): MvpService
{
    return new MvpService(mvp_pdo());
}

function mvp_is_logged(): bool
{
    if (isset($_SESSION['mvp_admin']) && $_SESSION['mvp_admin'] === true) {
        return true;
    }

    $token = $_COOKIE['galvao_mvp_auth'] ?? '';

    return is_string($token) && hash_equals(mvp_auth_token(), $token);
}

function mvp_require_login(): void
{
    if (!mvp_is_logged()) {
        header('Location: login.php');
        exit;
    }
}

function mvp_admin_email(): string
{
    global $config;
    return (string) ($config['admin_email'] ?? galvao_env('ADMIN_EMAIL', 'admin@galvao.local'));
}

function mvp_admin_password(): string
{
    global $config;
    return (string) ($config['admin_password'] ?? galvao_env('ADMIN_PASSWORD', 'Admin@12345'));
}

function mvp_auth_token(): string
{
    return hash_hmac('sha256', mvp_admin_email() . '|mvp-admin', mvp_admin_password());
}

function mvp_set_auth_cookie(): void
{
    setcookie('galvao_mvp_auth', mvp_auth_token(), [
        'expires' => time() + 86400,
        'path' => '/admin',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function mvp_clear_auth_cookie(): void
{
    setcookie('galvao_mvp_auth', '', [
        'expires' => time() - 3600,
        'path' => '/admin',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function mvp_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function mvp_active(string $page, string $current): string
{
    return $page === $current ? 'is-active' : '';
}

function mvp_whatsapp(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);

    if ($digits === '') {
        return '#';
    }

    if (!str_starts_with($digits, '55')) {
        $digits = '55' . $digits;
    }

    return 'https://wa.me/' . $digits;
}

function mvp_header(string $title, string $page): void
{
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= mvp_e($title); ?> · Galvão Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin-mvp.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="mobile-nav-head">
      <a class="brand" href="index.php">
        <img src="../public/assets/images/logo-galvao.png" alt="Galvão Lavagem Técnica">
      </a>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="admin-nav">
        <span></span>
        <span></span>
        <span></span>
        <span class="sr-only">Abrir menu</span>
      </button>
    </div>
    <nav class="nav" id="admin-nav">
      <a class="<?= mvp_active('dashboard', $page); ?>" href="index.php">Dashboard</a>
      <a class="<?= mvp_active('leads', $page); ?>" href="index.php?page=leads">Leads/Contatos</a>
      <a class="<?= mvp_active('agenda', $page); ?>" href="index.php?page=agenda">Agenda</a>
      <a class="<?= mvp_active('metricas', $page); ?>" href="index.php?page=metricas">Métricas</a>
      <a class="<?= mvp_active('config', $page); ?>" href="index.php?page=config">Configurações</a>
    </nav>
    <a class="logout" href="logout.php">Sair</a>
  </aside>
  <main class="main">
    <header class="topbar">
      <div>
        <p class="eyebrow">Sistema operacional simples</p>
        <h1><?= mvp_e($title); ?></h1>
      </div>
      <a class="pill" href="/" target="_blank">Ver site</a>
    </header>
<?php
}

function mvp_footer(): void
{
    ?>
  </main>
</div>
<script src="assets/js/admin-mvp.js"></script>
</body>
</html>
<?php
}
