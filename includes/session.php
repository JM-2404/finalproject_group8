<?php
/**
 * AccessAble — Session start + login checks (no database needed)
 * Owner: Member 4
 *
 * Loaded automatically by includes/functions.php. navigation.php also loads it,
 * so pages that don't use the database (e.g. index.php) still know who is logged in.
 */

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!function_exists('isLoggedIn')) {
    /** Session verification guard for protected routes. */
    function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    function isAdmin(): bool
    {
        return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
    }

    function currentUserId(): ?int
    {
        return isLoggedIn() ? (int) $_SESSION['user_id'] : null;
    }
}
