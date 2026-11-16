
<?php
/**
 * AccessAble — Application Entry Point
 *
 * Redirect visitors to the login page when they open the website.
 */

// Start the session and load shared authentication functions.
require_once __DIR__ . '/includes/functions.php';

// Send logged-in administrators to their admin dashboard.
if (isAdmin()) {
    redirect('admin/index.php');
}

// Send other logged-in users to their user dashboard.
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Send visitors who are not logged in to the login page.
redirect('login.php');
