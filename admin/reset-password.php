<?php

require_once __DIR__ . '/../core/bootstrap.php';

$error = null;
$success = null;
$rawToken = clean_text($_GET['token'] ?? $_POST['token'] ?? '');
$adminCssBundle = AssetService::adminBundle('css');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Connection::get($config);
    $logger = new SecurityLogger($pdo);

    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $error = 'Nao foi possivel validar a sessao. Atualize a pagina e tente novamente.';
        $logger->log('warning', 'csrf_failed_password_reset_submit', 'CSRF invalido no reset de senha.');
    } elseif (!str_contains($rawToken, ':')) {
        $error = 'Link de redefinicao invalido ou expirado.';
    } else {
        [$selector, $token] = explode(':', $rawToken, 2);
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');
        (new RateLimitService($pdo, $logger))->requireAllowed('password_reset_submit', 8, 3600, 'password_reset_submit|' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . '|' . $selector);

        if ($password !== $confirm) {
            $error = 'As senhas informadas nao coincidem.';
        } else {
            try {
                $auth = new AuthService($pdo, $logger, new RateLimitService($pdo, $logger));
                $success = $auth->resetPassword($selector, $token, $password)
                    ? 'Senha redefinida com seguranca. Voce ja pode acessar o painel.'
                    : null;
                $error = $success ? null : 'Link de redefinicao invalido ou expirado.';
            } catch (InvalidArgumentException $exception) {
                $error = $exception->getMessage();
            }
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nova senha | Admin Galvao</title>
    <?php if ($adminCssBundle): ?>
        <link rel="stylesheet" href="<?= e($adminCssBundle); ?>">
    <?php else: ?>
        <link rel="stylesheet" href="assets/css/admin.css">
    <?php endif; ?>
</head>
<body>
    <main class="login-screen">
        <form class="card login-card" action="reset-password.php" method="post" autocomplete="off">
            <?= csrf_field(); ?>
            <input type="hidden" name="token" value="<?= e($rawToken); ?>">
            <img src="../public/assets/images/logo-galvao.png" alt="Galvao Lavagem Tecnica" width="164" height="164" decoding="async">
            <div>
                <span class="eyebrow">Nova credencial</span>
                <h1>Criar nova senha</h1>
                <p class="muted">Use uma senha forte. Tokens anteriores e acessos persistentes serao revogados.</p>
            </div>
            <?php if ($error): ?>
                <div class="security-alert"><?= e($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="security-alert"><?= e($success); ?></div>
                <a class="btn btn--primary" href="login.php">Entrar no painel</a>
            <?php else: ?>
                <div class="field">
                    <label for="password">Nova senha</label>
                    <input class="input" id="password" name="password" type="password" autocomplete="new-password" required minlength="10">
                </div>
                <div class="field">
                    <label for="password_confirm">Confirmar senha</label>
                    <input class="input" id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required minlength="10">
                </div>
                <button class="btn btn--primary" type="submit">Redefinir senha</button>
            <?php endif; ?>
        </form>
    </main>
</body>
</html>
