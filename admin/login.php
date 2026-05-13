<?php
require_once __DIR__ . '/_bootstrap.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['_csrf_token'] ?? null)) {
        $error = 'Token de segurança inválido.';
    } elseif (hash_equals(mvp_admin_email(), (string) ($_POST['email'] ?? '')) && hash_equals(mvp_admin_password(), (string) ($_POST['password'] ?? ''))) {
        SessionService::regenerate();
        $_SESSION['mvp_admin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Acesso não autorizado.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login · Galvão Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin-mvp.css">
</head>
<body>
<main class="login-shell">
  <form class="card login-card" method="post">
    <img src="../public/assets/images/logo-galvao.png" alt="Galvão Lavagem Técnica">
    <p class="eyebrow">Acesso administrativo</p>
    <h1 class="section-title">Sistema operacional</h1>
    <?php if ($error !== ''): ?><p class="alert"><?= mvp_e($error); ?></p><?php endif; ?>
    <input type="hidden" name="_csrf_token" value="<?= mvp_e(csrf_token()); ?>">
    <label class="field"><span>E-mail</span><input name="email" type="email" autocomplete="username" required></label>
    <label class="field"><span>Senha</span><input name="password" type="password" autocomplete="current-password" required></label>
    <button class="btn" type="submit" style="width:100%">Entrar</button>
  </form>
</main>
</body>
</html>
