<?php

require_once __DIR__ . '/../core/bootstrap.php';

$message = null;
$debugLink = null;
$adminCssBundle = AssetService::adminBundle('css');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Connection::get($config);
    $logger = new SecurityLogger($pdo);

    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $logger->log('warning', 'csrf_failed_password_reset', 'CSRF invalido na recuperacao de senha.');
    } else {
        $auth = new AuthService($pdo, $logger, new RateLimitService($pdo, $logger));
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $token = $auth->createPasswordReset($email);

        if ($token) {
            $resetPath = '/admin/reset-password.php?token=' . rawurlencode($token);
            $resetUrl = rtrim((string) ($config['app_url'] ?? ''), '/') . $resetPath;
            $subject = 'Redefinicao de senha - Galvao Lavagem Tecnica';
            $body = "Solicitamos a redefinicao de senha do painel Galvao Lavagem Tecnica.\n\nAcesse o link temporario:\n{$resetUrl}\n\nSe voce nao solicitou, ignore esta mensagem.";

            @mail($email, $subject, $body, 'From: Galvao Lavagem Tecnica <no-reply@galvao.local>');

            if (($config['app_debug'] ?? false)) {
                $debugLink = $resetPath;
            }
        }
    }

    $message = 'Se o e-mail estiver ativo, enviaremos instrucoes para redefinir a senha.';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar senha | Admin Galvao</title>
    <?php if ($adminCssBundle): ?>
        <link rel="stylesheet" href="<?= e($adminCssBundle); ?>">
    <?php else: ?>
        <link rel="stylesheet" href="assets/css/admin.css">
    <?php endif; ?>
</head>
<body>
    <main class="login-screen">
        <form class="card login-card" action="forgot-password.php" method="post" autocomplete="on">
            <?= csrf_field(); ?>
            <img src="../public/assets/images/logo-galvao.png" alt="Galvao Lavagem Tecnica" width="164" height="164" decoding="async">
            <div>
                <span class="eyebrow">Recuperacao segura</span>
                <h1>Redefinir acesso</h1>
                <p class="muted">Informe o e-mail cadastrado. O link sera temporario e de uso unico.</p>
            </div>
            <?php if ($message): ?>
                <div class="security-alert"><?= e($message); ?></div>
            <?php endif; ?>
            <?php if ($debugLink): ?>
                <a class="security-alert" href="<?= e($debugLink); ?>">Link local de reset</a>
            <?php endif; ?>
            <div class="field">
                <label for="email">E-mail</label>
                <input class="input" id="email" name="email" type="email" autocomplete="email" required>
            </div>
            <button class="btn btn--primary" type="submit">Solicitar redefinicao</button>
            <a class="auth-link" href="login.php">Voltar para o login</a>
        </form>
    </main>
</body>
</html>
