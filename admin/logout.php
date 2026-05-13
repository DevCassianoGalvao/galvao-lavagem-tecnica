<?php
require_once __DIR__ . '/_bootstrap.php';

unset($_SESSION['mvp_admin']);
header('Location: login.php');
exit;
