<?php
/**
 * AccessAble — Log out
 * Owner: Member 4
 * Clears the session and its cookie, then returns to the homepage.
 */
require_once __DIR__ . '/includes/functions.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}
session_destroy();

// Start a clean session just to carry the goodbye message.
session_start();
session_regenerate_id(true);
setFlash('success', 'You have been logged out. See you soon!');

redirect('index.php');
