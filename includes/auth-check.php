<?php
/**
 * AccessAble — Login guard for protected pages
 * Owner: Member 4
 *
 * Put this at the very top of any page that needs a logged-in user
 * (dashboard.php, favourites.php, submit-review.php, ...):
 *   require_once __DIR__ . '/includes/auth-check.php';
 *
 * Visitors who are not logged in are sent to login.php and returned to the
 * page they wanted after signing in.
 */

require_once __DIR__ . '/functions.php';

if (!isLoggedIn()) {
    // Remember where the visitor was going (project-relative, e.g. "submit-review.php?venue_id=3").
    $root    = realpath(dirname(__DIR__));
    $script  = realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '';
    $relPath = ltrim(str_replace('\\', '/', substr($script, strlen((string) $root))), '/');
    $query   = $_SERVER['QUERY_STRING'] ?? '';
    $target  = safeRedirectTarget($relPath . ($query !== '' ? '?' . $query : ''));

    setFlash('info', 'Please log in to continue.');
    redirect('login.php?redirect=' . urlencode($target));
}

// The account may have been deactivated by an admin since login.
if (getCurrentUser() === null) {
    $_SESSION = [];
    session_regenerate_id(true);
    setFlash('error', 'Your account is no longer active. Please contact the AccessAble team.');
    redirect('login.php');
}
