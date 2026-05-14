<?php
require_once __DIR__ . '/_bootstrap.php';

$error = '';

function mvp_validate_login(string $email, string $password): bool
{
    if (hash_equals(mvp_admin_email(), $email) && hash_equals(mvp_admin_password(), $password)) {
        return true;
    }

    try {
        $stmt = mvp_pdo()->prepare('SELECT id, email, password_hash, status FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return is_array($user)
            && (($user['status'] ?? 'active') === 'active')
            && password_verify($password, (string) ($user['password_hash'] ?? ''));
    } catch (Throwable) {
        return false;
    }
}

function mvp_register_login_session(string $email): void
{
    SessionService::regenerate();
    $_SESSION['mvp_admin'] = true;
    $_SESSION['auth_user'] = [
        'id' => null,
        'email' => $email,
        'name' => 'Administrador',
        'role' => 'owner',
    ];
    session_write_close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (mvp_validate_login($email, $password)) {
        mvp_register_login_session($email);
        mvp_set_auth_cookie();
        header('Location: index.php');
        exit;
    }

    $error = 'E-mail ou senha inválidos.';
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
    <label class="field"><span>E-mail</span><input name="email" type="email" autocomplete="username" required></label>
    <label class="field"><span>Senha</span><input name="password" type="password" autocomplete="current-password" required></label>
    <button class="btn" type="submit" style="width:100%">Entrar</button>
  </form>
</main>
</body>
</html>
