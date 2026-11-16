<?php
/**
 * AccessAble — Report Processing
 * Handles resolving and dismissing venue reports.
 */

// Load shared functions and verify administrator access.
require_once __DIR__ . '/../includes/auth-check.php';

// Only accept POST requests with a valid CSRF token.
requirePostWithCsrf('admin/reviews.php');

// Connect to the database.
$pdo = getDB();

// Validate the submitted report ID.
$reportId = filter_var(
    $_POST['report_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

// Read the requested report status.
$newStatus = $_POST['status'] ?? '';

// Only these two actions are allowed.
$allowedStatuses = ['resolved', 'dismissed'];

// Reject invalid requests.
if (
    !$reportId ||
    !in_array($newStatus, $allowedStatuses, true)
) {
    setFlash('error', 'Invalid report moderation request.');
    redirect('admin/reviews.php');
}

try {

    // Update only reports that are currently open.
    $updateReport = $pdo->prepare(
        "UPDATE reports
         SET status = :status,
             resolved_at = CURRENT_TIMESTAMP
         WHERE id = :id
           AND status = 'open'"
    );

    $updateReport->execute([
        'status' => $newStatus,
        'id' => $reportId
    ]);

    // Check whether a report was actually updated.
    if ($updateReport->rowCount() === 0) {
        setFlash('error', 'Report not found or it has already been handled.');
        redirect('admin/reviews.php');
    }

    // Display the appropriate confirmation message.
    if ($newStatus === 'resolved') {
        setFlash('success', 'Report resolved successfully.');
    } else {
        setFlash('success', 'Report dismissed successfully.');
    }

    redirect('admin/reviews.php');

} catch (PDOException $e) {

    // Log the technical error without exposing it to the administrator.
    error_log('AccessAble report moderation failed: ' . $e->getMessage());

    setFlash('error', 'The report could not be updated. Please try again.');
    redirect('admin/reviews.php');
}