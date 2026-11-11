<?php
// auth.php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function check_login() {
    $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 7200;

    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }

    // Idle timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $lifetime) {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function current_user() {
    return $_SESSION['user'] ?? null;
}
