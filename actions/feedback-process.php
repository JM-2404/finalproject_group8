<?php
/**
 * SAVE A FEEDBACK REQUEST (Member 2)
 * Receives the form from send-request.php, checks it, saves it in the
 * feedback_requests table, then shows the user their requests.
 * The request is RECORDED in AccessAble. It is not emailed (prototype).
 */
require_once __DIR__ . '/../includes/functions.php';

// Only logged-in users may send requests.
if (!isLoggedIn()) {
    setFlash('info', 'Please log in to send a request.');
    redirect('login.php');
}

// Team helper: must be a POST with a valid CSRF token, otherwise bounce back.
requirePostWithCsrf('explore.php');

$userId  = currentUserId();
$venueId = (int) ($_POST['venue_id'] ?? 0);
$backTo  = 'send-request.php?venue_id=' . $venueId;
$db      = getDB();

// The venue must exist and be active. Prepared statement = safe from SQL injection.
$stmt = $db->prepare('SELECT id, name, contact_email FROM venues WHERE id = :id AND is_active = 1');
$stmt->execute(['id' => $venueId]);
$venue = $stmt->fetch();

if (!$venue) {
    setFlash('error', 'That venue could not be found.');
    redirect('explore.php');
}

// Only accept feature keys that really exist in the features table.
$validKeys = $db->query('SELECT feature_key FROM features')->fetchAll(PDO::FETCH_COLUMN);
$chosen    = array_values(array_intersect($validKeys, (array) ($_POST['features'] ?? [])));

$message = trim((string) ($_POST['message'] ?? ''));
$email   = trim((string) ($venue['contact_email'] ?? ''));

// Server-side validation (never trust the browser alone).
$errors = [];
if (!$chosen) {
    $errors[] = 'Please choose at least one feature.';
}
if (mb_strlen($message) < 10) {
    $errors[] = 'Please write at least 10 characters in your message.';
}
if (mb_strlen($message) > 1000) {
    $errors[] = 'Your message is too long (maximum 1000 characters).';
}
if ($email === '') {
    $errors[] = 'This venue has no contact address on file yet.';
}
if ($errors) {
    setFlash('error', implode(' ', $errors));
    redirect($backTo);
}

// Save the request.
$insert = $db->prepare(
    'INSERT INTO feedback_requests (user_id, venue_id, requested_features, message, recipient_email)
     VALUES (:user_id, :venue_id, :features, :message, :email)'
);
$insert->execute([
    'user_id'  => $userId,
    'venue_id' => $venueId,
    'features' => implode(',', $chosen),
    'message'  => $message,
    'email'    => $email,
]);

setFlash('success', 'Request submitted to ' . $venue['name'] . '. It has been recorded in AccessAble '
    . 'and addressed to ' . $email . '. This is a prototype, so no real email was sent.');

// Show the user all their requests, with the new one at the top.
redirect('my-requests.php');