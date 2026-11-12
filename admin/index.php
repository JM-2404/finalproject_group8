
<?php

// Temporary demonstration data
$adminName = "Administrator";

$adminStats = [
    "venues" => 12,
    "reviews" => 28,
    "reports" => 4,
    "users" => 35
];

$recentReports = [
    [
        "venue" => "Main University Building",
        "reason" => "Incorrect accessibility information",
        "status" => "Pending"
    ],
    [
        "venue" => "Student Centre",
        "reason" => "Missing accessibility details",
        "status" => "Pending"
    ],
    [
        "venue" => "Library",
        "reason" => "Incorrect venue information",
        "status" => "Under Review"
    ]
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | AccessAble</title>

    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>

<div class="admin-layout">

    <aside class="admin-sidebar">

        <h2>AccessAble</h2>

        <p class="sidebar-label">ADMINISTRATION</p>

        <nav>
            <a href="index.php" class="active">Dashboard</a>
            <a href="venues.php">Manage Venues</a>
            <a href="reviews.php">Manage Reviews</a>
            <a href="../dashboard.php">User Dashboard</a>
        </nav>

    </aside>

    <main class="admin-main">

        <header class="admin-header">
            <div>
                <p class="eyebrow">ADMIN PANEL</p>
                <h1>Welcome, <?php echo htmlspecialchars($adminName); ?></h1>
                <p>Manage accessibility information across Ashesi University.</p>
            </div>
        </header>

        <section class="admin-stats">

            <article class="admin-stat-card">
                <h2><?php echo $adminStats["venues"]; ?></h2>
                <p>Total venues</p>
            </article>

            <article class="admin-stat-card">
                <h2><?php echo $adminStats["reviews"]; ?></h2>
                <p>Total reviews</p>
            </article>

            <article class="admin-stat-card">
                <h2><?php echo $adminStats["reports"]; ?></h2>
                <p>Pending reports</p>
            </article>

            <article class="admin-stat-card">
                <h2><?php echo $adminStats["users"]; ?></h2>
                <p>Registered users</p>
            </article>

        </section>

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

                        <?php foreach ($recentReports as $report): ?>

                            <tr>
                                <td><?php echo htmlspecialchars($report["venue"]); ?></td>
                                <td><?php echo htmlspecialchars($report["reason"]); ?></td>
                                <td>
                                    <span class="status">
                                        <?php echo htmlspecialchars($report["status"]); ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>
</html>
