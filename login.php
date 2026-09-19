<?php
require_once __DIR__ . '/inc/auth.php';

jad_start_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim((string)($_POST['user'] ?? ''));
    $pass = (string)($_POST['pass'] ?? '');
    if (jad_attempt_login($user, $pass)) {
        header('Location: index.php');
        exit;
    }
    $_SESSION['jad_login_error'] = true;
}
header('Location: index.php');
exit;
