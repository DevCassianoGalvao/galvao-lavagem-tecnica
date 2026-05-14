<?php
require_once __DIR__ . '/_bootstrap.php';

unset($_SESSION['mvp_admin']);
mvp_clear_auth_cookie();
header('Location: login.php');
exit;
