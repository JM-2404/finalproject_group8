
<?php
/**
 * AccessAble — Favourite Processing
 * Handles adding and removing a user's saved venues.
 */

// Load the shared functions and require a logged-in user.
require_once __DIR__ . '/../includes/auth-check.php';

// Only accept POST requests with a valid CSRF token.
requirePostWithCsrf('favourites.php');

// Get the logged-in user's ID.
$userId = currentUserId();

// Read and validate the submitted venue ID.
$venueId = filter_input(INPUT_POST, 'venue_id', FILTER_VALIDATE_INT);

// Determine whether the user wants to add or remove a favourite.
$action = $_POST['action'] ?? '';

// Validate the submitted information.
if (!$venueId || $venueId < 1 || !in_array($action, ['add', 'remove'], true)) {
    setFlash('error', 'Invalid favourite request.');
    redirect('favourites.php');
}

// Process the requested action.
if ($action === 'add') {

    // Save the venue for this user.
    if (addFavourite($userId, $venueId)) {
        setFlash('success', 'Venue added to your favourites.');
    } else {
        setFlash('error', 'Could not add this venue. It may no longer be available.');
    }

} elseif ($action === 'remove') {

    // Remove only this user's saved venue.
    if (removeFavourite($userId, $venueId)) {
        setFlash('success', 'Venue removed from your favourites.');
    } else {
        setFlash('info', 'This venue was not in your favourites.');
    }
}

// Return the user to their favourites page.
redirect('favourites.php');
