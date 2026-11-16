<?php
/**
 * AccessAble — Review and Report Moderation
 * Allows administrators to moderate reviews and manage venue reports.
 */

// Load shared functions and check administrator access.
require_once __DIR__ . '/../includes/functions.php';

// Only administrators can access this page.
if (!isAdmin()) {
    redirect(isLoggedIn() ? 'dashboard.php' : 'login.php');
}

// Connect to the database.
$pdo = getDB();

// ============================================================
// HANDLE REVIEW MODERATION
// ============================================================

// Process review status changes submitted from this page.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'])) {

    // Verify the CSRF token before changing anything.
    requirePostWithCsrf('admin/reviews.php');

    // Validate the review ID.
    $reviewId = filter_var(
        $_POST['review_id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    // Only these review statuses are allowed.
    $allowedStatuses = ['approved', 'hidden'];
    $newStatus = $_POST['status'] ?? '';

    if (!$reviewId || !in_array($newStatus, $allowedStatuses, true)) {
        setFlash('error', 'Invalid review moderation request.');
        redirect('admin/reviews.php');
    }

    // Update the selected review.
    $updateReview = $pdo->prepare(
        "UPDATE reviews SET status = :status WHERE id = :id"
    );

    $updateReview->execute([
        'status' => $newStatus,
        'id' => $reviewId
    ]);

    setFlash('success', 'Review status updated successfully.');
    redirect('admin/reviews.php');
}

// ============================================================
// GET REVIEW FILTER
// ============================================================

// Show all reviews by default, or filter by a selected status.
$reviewFilter = $_GET['review_status'] ?? 'all';
$validReviewFilters = ['all', 'approved', 'pending', 'hidden'];

if (!in_array($reviewFilter, $validReviewFilters, true)) {
    $reviewFilter = 'all';
}

// Retrieve reviews with their author and venue information.
$reviewSql = "
    SELECT
        r.id,
        r.rating,
        r.accessibility_rating,
        r.title,
        r.comment,
        r.status,
        r.created_at,
        u.full_name,
        v.name AS venue_name
    FROM reviews r
    INNER JOIN users u ON r.user_id = u.id
    INNER JOIN venues v ON r.venue_id = v.id
";

if ($reviewFilter !== 'all') {
    $reviewSql .= " WHERE r.status = :status";
}

$reviewSql .= " ORDER BY r.created_at DESC";

$reviewQuery = $pdo->prepare($reviewSql);

if ($reviewFilter !== 'all') {
    $reviewQuery->execute(['status' => $reviewFilter]);
} else {
    $reviewQuery->execute();
}

$reviews = $reviewQuery->fetchAll(PDO::FETCH_ASSOC);

// Count reviews in each status.
$reviewCounts = $pdo->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'approved') AS approved,
        SUM(status = 'hidden') AS hidden
     FROM reviews"
)->fetch(PDO::FETCH_ASSOC);

// ============================================================
// GET REPORTS
// ============================================================

// Retrieve reports with the venue and reporting user information.
$reportQuery = $pdo->query(
    "SELECT
        rp.id,
        rp.venue_id,
        rp.reason,
        rp.details,
        rp.status,
        rp.created_at,
        u.full_name,
        v.name AS venue_name
     FROM reports rp
     LEFT JOIN users u ON rp.user_id = u.id
     INNER JOIN venues v ON rp.venue_id = v.id
     ORDER BY
        CASE WHEN rp.status = 'open' THEN 0 ELSE 1 END,
        rp.created_at DESC"
);

$reports = $reportQuery->fetchAll(PDO::FETCH_ASSOC);

// Count reports in each status.
$reportCounts = $pdo->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'open') AS open,
        SUM(status = 'resolved') AS resolved,
        SUM(status = 'dismissed') AS dismissed
     FROM reports"
)->fetch(PDO::FETCH_ASSOC);

// Convert report reason codes into readable labels.
$reportReasons = [
    'incorrect_feature' => 'Incorrect accessibility feature',
    'closed' => 'Venue is closed',
    'wrong_details' => 'Incorrect venue details',
    'inappropriate_review' => 'Inappropriate review',
    'other' => 'Other'
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Review and Report Moderation | AccessAble</title>

    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>

    <main class="admin-main">

        <section class="admin-page-header">
            <h1>Review and Report Moderation</h1>
            <p>Manage user reviews and reports submitted about campus venues.</p>
        </section>

        <?php displayFlash(); ?>

        <!-- ==================================================
             REVIEW STATISTICS
        =================================================== -->

        <section class="admin-stats">

            <div class="stat-card">
                <h3>Total Reviews</h3>
                <p><?php echo (int) ($reviewCounts['total'] ?? 0); ?></p>
            </div>

            <div class="stat-card">
                <h3>Pending Reviews</h3>
                <p><?php echo (int) ($reviewCounts['pending'] ?? 0); ?></p>
            </div>

            <div class="stat-card">
                <h3>Approved Reviews</h3>
                <p><?php echo (int) ($reviewCounts['approved'] ?? 0); ?></p>
            </div>

            <div class="stat-card">
                <h3>Hidden Reviews</h3>
                <p><?php echo (int) ($reviewCounts['hidden'] ?? 0); ?></p>
            </div>

        </section>

        <!-- ==================================================
             REVIEW MODERATION TABLE
        =================================================== -->

        <section class="admin-section">

            <div class="admin-section-header">
                <h2>User Reviews</h2>

                <!-- Filter reviews by their moderation status. -->
                <form method="GET" action="reviews.php" class="venue-filter-form">
                    <label for="review_status">Filter:</label>

                    <select id="review_status" name="review_status">
                        <option value="all" <?php echo $reviewFilter === 'all' ? 'selected' : ''; ?>>
                            All Reviews
                        </option>

                        <option value="pending" <?php echo $reviewFilter === 'pending' ? 'selected' : ''; ?>>
                            Pending
                        </option>

                        <option value="approved" <?php echo $reviewFilter === 'approved' ? 'selected' : ''; ?>>
                            Approved
                        </option>

                        <option value="hidden" <?php echo $reviewFilter === 'hidden' ? 'selected' : ''; ?>>
                            Hidden
                        </option>
                    </select>

                    <button type="submit" class="btn btn-primary">Apply</button>
                </form>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>Review</th>
                            <th>User</th>
                            <th>Venue</th>
                            <th>Ratings</th>
                            <th>Status</th>
                            <th>Moderation</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($reviews)): ?>

                            <tr>
                                <td colspan="6">No reviews found.</td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($reviews as $review): ?>

                                <tr>
                                    <td>
                                        <strong><?php echo e($review['title']); ?></strong>
                                        <p><?php echo e($review['comment']); ?></p>
                                        <small>
                                            <?php echo e($review['created_at']); ?>
                                        </small>
                                    </td>

                                    <td><?php echo e($review['full_name']); ?></td>

                                    <td><?php echo e($review['venue_name']); ?></td>

                                    <td>
                                        Overall: <?php echo (int) $review['rating']; ?>/5
                                        <br>
                                        Accessibility: <?php echo (int) $review['accessibility_rating']; ?>/5
                                    </td>

                                    <td>
                                        <span class="status-badge status-<?php echo e($review['status']); ?>">
                                            <?php echo e(ucfirst($review['status'])); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="venue-actions">

                                            <?php if ($review['status'] !== 'approved'): ?>

                                                <!-- Approve the review. -->
                                                <form method="POST" action="reviews.php">
                                                    <?php echo csrfField(); ?>

                                                    <input type="hidden" name="review_id"
                                                           value="<?php echo (int) $review['id']; ?>">

                                                    <input type="hidden" name="status"
                                                           value="approved">

                                                    <button type="submit" class="activate-button">
                                                        Approve
                                                    </button>
                                                </form>

                                            <?php endif; ?>

                                            <?php if ($review['status'] !== 'hidden'): ?>

                                                <!-- Hide the review from public display. -->
                                                <form method="POST" action="reviews.php">
                                                    <?php echo csrfField(); ?>

                                                    <input type="hidden" name="review_id"
                                                           value="<?php echo (int) $review['id']; ?>">

                                                    <input type="hidden" name="status"
                                                           value="hidden">

                                                    <button type="submit" class="deactivate-button">
                                                        Hide
                                                    </button>
                                                </form>

                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>
            </div>

        </section>

        <!-- ==================================================
             REPORT STATISTICS
        =================================================== -->

        <section class="admin-stats">

            <div class="stat-card">
                <h3>Total Reports</h3>
                <p><?php echo (int) ($reportCounts['total'] ?? 0); ?></p>
            </div>

            <div class="stat-card">
                <h3>Open Reports</h3>
                <p><?php echo (int) ($reportCounts['open'] ?? 0); ?></p>
            </div>

            <div class="stat-card">
                <h3>Resolved Reports</h3>
                <p><?php echo (int) ($reportCounts['resolved'] ?? 0); ?></p>
            </div>

            <div class="stat-card">
                <h3>Dismissed Reports</h3>
                <p><?php echo (int) ($reportCounts['dismissed'] ?? 0); ?></p>
            </div>

        </section>

        <!-- ==================================================
             REPORT MANAGEMENT TABLE
        =================================================== -->

        <section class="admin-section">

            <h2>Venue Reports</h2>

            <div class="admin-table-container">
                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>Venue</th>
                            <th>Reported By</th>
                            <th>Reason</th>
                            <th>Details</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($reports)): ?>

                            <tr>
                                <td colspan="7">No reports have been submitted.</td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($reports as $report): ?>

                                <tr>
                                    <td><?php echo e($report['venue_name']); ?></td>

                                    <td>
                                        <?php echo e($report['full_name'] ?? 'Unknown user'); ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo e(
                                            $reportReasons[$report['reason']]
                                            ?? $report['reason']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php echo e($report['details'] ?? 'No additional details'); ?>
                                    </td>

                                    <td><?php echo e($report['created_at']); ?></td>

                                    <td>
                                        <span class="status-badge status-<?php echo e($report['status']); ?>">
                                            <?php echo e(ucfirst($report['status'])); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if ($report['status'] === 'open'): ?>

                                            <!-- Send report status changes to the processing script. -->
                                            <div class="venue-actions">

                                                <form method="POST" action="../actions/report-process.php">
                                                    <?php echo csrfField(); ?>

                                                    <input type="hidden" name="report_id"
                                                           value="<?php echo (int) $report['id']; ?>">

                                                    <input type="hidden" name="status"
                                                           value="resolved">

                                                    <button type="submit" class="activate-button">
                                                        Resolve
                                                    </button>
                                                </form>

                                                <form method="POST" action="../actions/report-process.php">
                                                    <?php echo csrfField(); ?>

                                                    <input type="hidden" name="report_id"
                                                           value="<?php echo (int) $report['id']; ?>">

                                                    <input type="hidden" name="status"
                                                           value="dismissed">

                                                    <button type="submit" class="deactivate-button">
                                                        Dismiss
                                                    </button>
                                                </form>

                                            </div>

                                        <?php else: ?>

                                            <span>Completed</span>

                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>
            </div>

        </section>

    </main>

</body>
</html>