<?php
/**
 * AccessAble — Login handler
 * Owner: Member 4
 * Verifies credentials with password_verify(), starts a fresh session and
 * slows down repeated failed attempts.
 */
require_once __DIR__ . '/../includes/functions.php';

const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_SECONDS    = 900; // 15 minutes

requirePostWithCsrf('login.php');

$email    = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$redirect = safeRedirectTarget($_POST['redirect'] ?? '', '');
$backTo   = 'login.php' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : '');

// ---- Throttle repeated failures ---------------------------------------------------
$attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'first' => time()];
if (time() - $attempts['first'] > LOCKOUT_SECONDS) {
    $attempts = ['count' => 0, 'first' => time()];
}
if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
    $wait = (int) ceil((LOCKOUT_SECONDS - (time() - $attempts['first'])) / 60);
    setOldInput(['email' => $email]);
    setFormErrors(['general' => "Too many failed attempts. Please wait {$wait} minute(s) and try again."]);
    redirect($backTo);
}

// ---- Validate input ----------------------------------------------------------------
$errors = [];
if ($email === '') {
    $errors['email'] = 'Please enter your email address.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}
if ($password === '') {
    $errors['password'] = 'Please enter your password.';
}
if ($errors) {
    setOldInput(['email' => $email]);
    setFormErrors($errors);
    redirect($backTo);
}

// ---- Check credentials --------------------------------------------------------------
$user = getUserByEmail($email);

// Same message for unknown email and wrong password, so accounts can't be discovered.
if (!$user || !password_verify($password, $user['password_hash'])) {
    $attempts['count']++;
    $_SESSION['login_attempts'] = $attempts;
    setOldInput(['email' => $email]);
    setFormErrors(['general' => 'Incorrect email or password.']);
    redirect($backTo);
}

if (!(int) $user['is_active']) {
    setOldInput(['email' => $email]);
    setFormErrors(['general' => 'This account has been deactivated. Please contact the AccessAble team.']);
    redirect($backTo);
}

// Upgrade the stored hash if PHP's default algorithm has changed.
if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
    getDB()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
}

unset($_SESSION['login_attempts']);
clearOldInput();
loginUser($user);

$firstName = explode(' ', $user['full_name'])[0];
setFlash('success', 'Welcome back, ' . $firstName . '!');

if ($redirect !== '') {
    redirect($redirect);
}
redirect(landingPage($user['role'] === 'admin'));
