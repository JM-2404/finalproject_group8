<?php
/**
 * AccessAble — Review submission handler
 * Owner: Member 4
 *
 * POST actions:
 *   action=save    create the user's review, or update it if they already reviewed this venue
 *   action=delete  remove the user's own review
 *
 * Only logged-in users can review ("verified reviews"), one review per user per venue.
 */
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    setFlash('info', 'Please log in to write a review.');
    redirect('login.php');
}

$venueId = filter_var($_POST['venue_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$formUrl = 'submit-review.php?venue_id=' . (int) $venueId;

requirePostWithCsrf($venueId ? $formUrl : 'explore.php');

$venue = $venueId ? getVenueById($venueId) : null;
if (!$venue) {
    setFlash('error', 'That venue could not be found.');
    redirect('explore.php');
}

$userId     = currentUserId();
$action     = $_POST['action'] ?? 'save';
// Back to the venue page; until Member 3's venue-details.php exists, back to the review form.
$detailsUrl = pageExists('venue-details.php') ? venueUrl((int) $venue['id'], 'reviews') : $formUrl;
$existing   = getUserReview($userId, (int) $venue['id']);

// ---- Delete --------------------------------------------------------------------------
if ($action === 'delete') {
    if ($existing) {
        getDB()->prepare('DELETE FROM reviews WHERE id = ? AND user_id = ?')->execute([$existing['id'], $userId]);
        setFlash('success', 'Your review has been deleted.');
    } else {
        setFlash('info', 'You have not reviewed this venue.');
    }
    redirect($detailsUrl);
}

// ---- Validate --------------------------------------------------------------------------
$rating     = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
$accRating  = filter_var($_POST['accessibility_rating'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
$title      = trim(preg_replace('/\s+/', ' ', (string) ($_POST['title'] ?? '')));
$comment    = trim(str_replace("\r\n", "\n", (string) ($_POST['comment'] ?? '')));
$visitDate  = trim((string) ($_POST['visit_date'] ?? ''));

$errors = [];

if ($rating === false) {
    $errors['rating'] = 'Please choose an overall rating from 1 to 5 stars.';
}
if ($accRating === false) {
    $errors['accessibility_rating'] = 'Please choose an accessibility rating from 1 to 5 stars.';
}

if ($title === '') {
    $errors['title'] = 'Please give your review a title.';
} elseif (mb_strlen($title) < 3 || mb_strlen($title) > 120) {
    $errors['title'] = 'Title must be between 3 and 120 characters.';
}

$commentLength = mb_strlen($comment);
if ($comment === '') {
    $errors['comment'] = 'Please write your review.';
} elseif ($commentLength < 20) {
    $errors['comment'] = 'Please write at least 20 characters so your review is helpful to others.';
} elseif ($commentLength > 2000) {
    $errors['comment'] = 'Reviews can be up to 2,000 characters.';
}

$visitDateValue = null;
if ($visitDate !== '') {
    $date = DateTime::createFromFormat('!Y-m-d', $visitDate);
    if (!$date || $date->format('Y-m-d') !== $visitDate) {
        $errors['visit_date'] = 'Please enter a valid date.';
    } elseif ($date > new DateTime('today')) {
        $errors['visit_date'] = 'Visit date cannot be in the future.';
    } elseif ($date < new DateTime('-10 years')) {
        $errors['visit_date'] = 'Please enter a visit from the last 10 years.';
    } else {
        $visitDateValue = $visitDate;
    }
}

if ($errors) {
    setOldInput([
        'rating' => $rating ?: '', 'accessibility_rating' => $accRating ?: '',
        'title' => $title, 'comment' => $comment, 'visit_date' => $visitDate,
    ]);
    setFormErrors($errors);
    redirect($formUrl);
}

// ---- Save (insert or update the user's single review) -----------------------------------
// A review hidden by a moderator goes back to 'pending' when edited; otherwise it stays as is.
$status = ($existing && $existing['status'] === 'hidden') ? 'pending' : ($existing['status'] ?? 'approved');

try {
    $stmt = getDB()->prepare(
        'INSERT INTO reviews (venue_id, user_id, rating, accessibility_rating, title, comment, visit_date, status)
         VALUES (:venue_id, :user_id, :rating, :acc, :title, :comment, :visit_date, :status)
         ON DUPLICATE KEY UPDATE
             rating = VALUES(rating), accessibility_rating = VALUES(accessibility_rating),
             title = VALUES(title), comment = VALUES(comment), visit_date = VALUES(visit_date),
             status = VALUES(status)'
    );
    $stmt->execute([
        'venue_id'   => $venue['id'],
        'user_id'    => $userId,
        'rating'     => $rating,
        'acc'        => $accRating,
        'title'      => $title,
        'comment'    => $comment,
        'visit_date' => $visitDateValue,
        'status'     => $status,
    ]);
} catch (PDOException $e) {
    error_log('AccessAble review save failed: ' . $e->getMessage());
    setOldInput([
        'rating' => $rating, 'accessibility_rating' => $accRating,
        'title' => $title, 'comment' => $comment, 'visit_date' => $visitDate,
    ]);
    setFormErrors(['general' => 'Sorry, your review could not be saved. Please try again.']);
    redirect($formUrl);
}

clearOldInput();
if ($status === 'pending') {
    setFlash('success', 'Thanks! Your updated review will appear once a moderator has checked it.');
} else {
    setFlash('success', $existing ? 'Your review has been updated.' : 'Thank you! Your review has been published.');
}
redirect($detailsUrl);
