
<?php

// ============================================================
// 1. LOAD SHARED FUNCTIONS AND CHECK ADMIN ACCESS
// ============================================================

// Load the database connection, session and shared functions.
// The ../ path moves from the admin folder to the project root.
require_once __DIR__ . '/../includes/functions.php';

// Prevent ordinary users from accessing the admin dashboard.
if (!isAdmin()) {

    // If the visitor is logged in but is not an administrator,
    // send them to their normal user dashboard.
    if (isLoggedIn()) {
        setFlash('error', 'You do not have permission to access the admin area.');
        redirect('dashboard.php');
    }

    // If the visitor is not logged in, send them to login.
    setFlash('info', 'Please log in to access the admin area.');
    redirect('login.php');
}

// Confirm that the administrator's account is still active.
$admin = getCurrentUser();

if ($admin === null) {
    $_SESSION = [];
    session_regenerate_id(true);

    setFlash('error', 'Your account is no longer active.');
    redirect('login.php');
}

// Get the administrator's actual name from the database.
$adminName = $admin['full_name'];


// ============================================================
// 2. CONNECT TO THE DATABASE
// ============================================================

// Get the shared PDO database connection.
$pdo = getDB();


// ============================================================
// 3. RETRIEVE ADMIN DASHBOARD STATISTICS
// ============================================================

// Count all active venues in the database.
$venueQuery = $pdo->query(
    "SELECT COUNT(*) FROM venues WHERE is_active = 1"
);

$totalVenues = (int) $venueQuery->fetchColumn();


// Count all reviews, regardless of their moderation status.
$reviewQuery = $pdo->query(
    "SELECT COUNT(*) FROM reviews"
);

$totalReviews = (int) $reviewQuery->fetchColumn();


// Count reports that are still open and require attention.
$reportQuery = $pdo->query(
    "SELECT COUNT(*) FROM reports WHERE status = 'open'"
);

$pendingReports = (int) $reportQuery->fetchColumn();


// Count active registered user accounts.
$userQuery = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE is_active = 1"
);

$totalUsers = (int) $userQuery->fetchColumn();


// Store the statistics together for convenient display.
$adminStats = [
    'venues' => $totalVenues,
    'reviews' => $totalReviews,
    'reports' => $pendingReports,
    'users' => $totalUsers
];


// ============================================================
// 4. RETRIEVE RECENT REPORTS
// ============================================================

// Retrieve the five most recently submitted reports.
// Joining venues allows us to display the venue name.
$recentReportsQuery = $pdo->query(
    "SELECT
        r.id,
        r.reason,
        r.details,
        r.status,
        r.created_at,
        v.name AS venue_name
     FROM reports r
     LEFT JOIN venues v ON v.id = r.venue_id
     ORDER BY r.created_at DESC
     LIMIT 5"
);

// Store the retrieved reports in an array.
$recentReports = $recentReportsQuery->fetchAll();


// ============================================================
// 5. SET PAGE TITLE
// ============================================================

$pageTitle = 'Admin Dashboard | AccessAble';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <!-- Set character encoding and responsive viewport. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Set the browser tab title. -->
    <title><?php echo e($pageTitle); ?></title>

    <!-- Load the admin dashboard stylesheet. -->
    <link rel="stylesheet" href="../css/admin.css">

</head>

<body>

<div class="admin-layout">

    <!-- ======================================================
         6. ADMIN SIDEBAR
         ====================================================== -->

    <aside class="admin-sidebar">

        <h2>AccessAble</h2>

        <p class="sidebar-label">ADMINISTRATION</p>

        <!-- Navigation links for the admin section. -->
        <nav>
            <a href="index.php" class="active">Dashboard</a>
            <a href="venues.php">Manage Venues</a>
            <a href="reviews.php">Manage Reviews</a>
            <a href="../dashboard.php">User Dashboard</a>
            <a href="../logout.php">Log out</a>
        </nav>

    </aside>


    <!-- ======================================================
         7. ADMIN MAIN CONTENT
         ====================================================== -->

    <main class="admin-main">

        <!-- Display the administrator's name. -->
        <header class="admin-header">

            <div>
                <p class="eyebrow">ADMIN PANEL</p>

                <h1>
                    Welcome, <?php echo e($adminName); ?>
                </h1>

                <p>
                    Manage accessibility information across Ashesi University.
                </p>
            </div>

        </header>


        <!-- Display any success or error messages. -->
        <?php displayFlash(); ?>


        <!-- ==================================================
             8. ADMIN STATISTICS
             ================================================== -->

        <section class="admin-stats">

            <!-- Display the total number of active venues. -->
            <article class="admin-stat-card">
                <h2><?php echo $adminStats['venues']; ?></h2>
                <p>Total venues</p>
            </article>

            <!-- Display the total number of reviews. -->
            <article class="admin-stat-card">
                <h2><?php echo $adminStats['reviews']; ?></h2>
                <p>Total reviews</p>
            </article>

            <!-- Display the number of reports awaiting attention. -->
            <article class="admin-stat-card">
                <h2><?php echo $adminStats['reports']; ?></h2>
                <p>Pending reports</p>
            </article>

            <!-- Display the number of active registered users. -->
            <article class="admin-stat-card">
                <h2><?php echo $adminStats['users']; ?></h2>
                <p>Registered users</p>
            </article>

        </section>


        <!-- ==================================================
             9. RECENT REPORTS
             ================================================== -->

        <section class="reports-section">

            <h2>Recent Reports</h2>

            <div class="reports-table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>Venue</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!empty($recentReports)): ?>

                            <!-- Display each report retrieved from the database. -->
                            <?php foreach ($recentReports as $report): ?>

                                <tr>

                                    <!-- Display the reported venue. -->
                                    <td>
                                        <?php echo e($report['venue_name'] ?? 'Unknown venue'); ?>
                                    </td>

                                    <!-- Display the report reason. -->
                                    <td>
                                        <?php echo e(ucwords(str_replace('_', ' ', $report['reason']))); ?>
                                    </td>

                                    <!-- Display the report status. -->
                                    <td>
                                        <span class="status">
                                            <?php echo e(ucfirst($report['status'])); ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <!-- Display this message if no reports exist. -->
                            <tr>
                                <td colspan="3">
                                    No reports have been submitted yet.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>
</html>
