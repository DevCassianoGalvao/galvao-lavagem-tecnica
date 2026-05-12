<?php

require_once __DIR__ . '/../core/bootstrap.php';

$error = null;
$adminCssBundle = AssetService::adminBundle('css');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Connection::get($config);
    $logger = new SecurityLogger($pdo);
    $rateLimiter = new RateLimitService($pdo, $logger);

    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $logger->log('warning', 'csrf_failed_login', 'CSRF invalido no login.');
        $error = 'Nao foi possivel validar a sessao. Atualize a pagina e tente novamente.';
    } else {
        $auth = new AuthService($pdo, $logger, $rateLimiter);
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');
        $remember = (string) ($_POST['remember'] ?? '') === '1';

        if ($auth->attempt($email, $password, $remember)) {
            header('Location: /admin/');
            exit;
        }

        $error = 'Credenciais invalidas ou acesso indisponivel.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Admin Galvao</title>
    <?php if ($adminCssBundle): ?>
        <link rel="preload" href="<?= e($adminCssBundle); ?>" as="style">
        <link rel="stylesheet" href="<?= e($adminCssBundle); ?>">
    <?php else: ?>
        <link rel="stylesheet" href="assets/css/admin.css">
    <?php endif; ?>
</head>
<body>
    <main class="login-screen">
        <form class="card login-card" action="login.php" method="post" autocomplete="on">
            <?= csrf_field(); ?>
            <img src="../public/assets/images/logo-galvao.png" alt="Galvao Lavagem Tecnica" width="164" height="164" decoding="async">
            <div>
                <span class="eyebrow">Acesso restrito</span>
                <h1>Admin Galvao</h1>
                <p class="muted">Ambiente protegido para operacao interna.</p>
            </div>
            <?php if ($error): ?>
                <div class="security-alert"><?= e($error); ?></div>
            <?php endif; ?>
            <div class="field">
                <label for="email">E-mail</label>
                <input class="input" id="email" name="email" type="email" autocomplete="email" required>
            </div>
            <div class="field">
                <label for="password">Senha</label>
                <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <div class="auth-options">
                <label class="backup-toggle">
                    <input type="checkbox" name="remember" value="1">
                    <span>Manter conectado neste dispositivo</span>
                </label>
                <a href="forgot-password.php">Esqueci minha senha</a>
            </div>
            <button class="btn btn--primary" type="submit">Entrar</button>
        </form>
    </main>
</body>
</html>
