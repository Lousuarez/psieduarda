<?php
require_once __DIR__ . '/config.php';

function jad_start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function jad_is_authed(): bool {
    jad_start_session();
    return !empty($_SESSION['jad_authed']);
}

/** Usado no topo de cada endpoint em api/ — corta a requisição com 401 se não estiver logado. */
function jad_require_auth(): void {
    if (!jad_is_authed()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'not_authenticated']);
        exit;
    }
}

function jad_attempt_login(string $user, string $pass): bool {
    $cfg = jad_config();
    if (!hash_equals((string)($cfg['admin_user'] ?? ''), $user)) {
        return false;
    }
    if (!password_verify($pass, (string)($cfg['admin_pass_hash'] ?? ''))) {
        return false;
    }
    jad_start_session();
    session_regenerate_id(true);
    $_SESSION['jad_authed'] = true;
    return true;
}

function jad_logout(): void {
    jad_start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
