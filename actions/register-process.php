<?php
/**
 * AccessAble — Registration handler
 * Owner: Member 4
 * Receives POST from register.php, validates on the server, hashes the
 * password with password_hash() and logs the new user in.
 */
require_once __DIR__ . '/../includes/functions.php';

requirePostWithCsrf('register.php');

if (isLoggedIn()) {
    redirect(landingPage());
}

$fullName  = trim(preg_replace('/\s+/', ' ', (string) ($_POST['full_name'] ?? '')));
$email     = strtolower(trim((string) ($_POST['email'] ?? '')));
$password  = (string) ($_POST['password'] ?? '');
$confirm   = (string) ($_POST['confirm_password'] ?? '');
$terms     = ($_POST['terms'] ?? '') === '1';
$redirect  = safeRedirectTarget($_POST['redirect'] ?? '', '');

$needs = $_POST['needs'] ?? [];
$needs = is_array($needs) ? array_values(array_filter($needs, 'is_string')) : [];
$validKeys = array_column(getFeatures(), 'feature_key');
$needs = array_values(array_intersect($needs, $validKeys));

$backTo = 'register.php' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : '');

// ---- Validation --------------------------------------------------------------
$errors = [];

if ($fullName === '') {
    $errors['full_name'] = 'Please enter your name.';
} elseif (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
    $errors['full_name'] = 'Name must be between 2 and 100 characters.';
} elseif (!preg_match("/^[\p{L}\p{M}][\p{L}\p{M} .'\-]*$/u", $fullName)) {
    $errors['full_name'] = 'Name can only contain letters, spaces, hyphens, apostrophes and full stops.';
}

if ($email === '') {
    $errors['email'] = 'Please enter your email address.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    $errors['email'] = 'Please enter a valid email address.';
} elseif (getUserByEmail($email)) {
    $errors['email'] = 'An account with this email already exists. Try logging in instead.';
}

if ($password === '') {
    $errors['password'] = 'Please choose a password.';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
} elseif (strlen($password) > 72) {
    $errors['password'] = 'Password must be 72 characters or fewer.';
} elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    $errors['password'] = 'Password must include at least one letter and one number.';
}

if (!isset($errors['password']) && $password !== $confirm) {
    $errors['confirm_password'] = 'Passwords do not match.';
}

if (!$terms) {
    $errors['terms'] = 'Please confirm you will write honest reviews.';
}

if ($errors) {
    setOldInput(['full_name' => $fullName, 'email' => $email, 'terms' => $terms ? '1' : '', 'needs' => $needs]);
    setFormErrors($errors);
    redirect($backTo);
}

// ---- Create the account ---------------------------------------------------------
try {
    $stmt = getDB()->prepare(
        "INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'user')"
    );
    $stmt->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $userId = (int) getDB()->lastInsertId();

    if ($needs) {
        saveUserPreferences($userId, $needs);
    }
} catch (PDOException $e) {
    // 23000 = duplicate email created between our check and the insert
    if ($e->getCode() === '23000') {
        setFormErrors(['email' => 'An account with this email already exists. Try logging in instead.']);
    } else {
        error_log('AccessAble registration failed: ' . $e->getMessage());
        setFormErrors(['general' => 'Something went wrong creating your account. Please try again.']);
    }
    setOldInput(['full_name' => $fullName, 'email' => $email, 'terms' => $terms ? '1' : '', 'needs' => $needs]);
    redirect($backTo);
}

clearOldInput();
loginUser(['id' => $userId, 'full_name' => $fullName, 'email' => $email, 'role' => 'user']);
setFlash('success', 'Welcome to AccessAble, ' . $fullName . '! Your account has been created.');
redirect($redirect !== '' ? $redirect : landingPage());
