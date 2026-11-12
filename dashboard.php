
<?php

// Temporary demonstration data
$userName = "";

$dashboardStats = [
    "favourites" => 4,
    "reviews" => 3,
    "recently_viewed" => 6
];

$recentVenues = [
    [
        "name" => "Main University Building",
        "location" => "Ashesi University",
        "score" => 85
    ],
    [
        "name" => "Library",
        "location" => "Ashesi University",
        "score" => 90
    ],
    [
        "name" => "Student Centre",
        "location" => "Ashesi University",
        "score" => 75
    ]
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Dashboard | AccessAble</title>

    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>

    <main class="dashboard">

        <header class="dashboard-header">
            <div>
                <p class="eyebrow">MY DASHBOARD</p>

                <h1>Welcome <?php echo htmlspecialchars($userName); ?>!</h1>

                <p>
                    Keep track of your favourite places
                    around Ashesi University.
                </p>
            </div>

            <a href="explore.php" class="explore-button">
                Explore venues
            </a>
        </header>

        <section class="stats-grid">

            <div class="stat-card">
                <h2><?php echo $dashboardStats["favourites"]; ?></h2>
                <p>Saved venues</p>
            </div>

            <div class="stat-card">
                <h2><?php echo $dashboardStats["reviews"]; ?></h2>
                <p>Reviews submitted</p>
            </div>

            <div class="stat-card">
                <h2><?php echo $dashboardStats["recently_viewed"]; ?></h2>
                <p>Recently viewed</p>
            </div>

        </section>

        <section class="recent-section">

            <div class="section-heading">
                <div>
                    <h2>Recently viewed venues</h2>
                    <p>Your recently explored campus locations.</p>
                </div>

                <a href="favourites.php">View favourites</a>
            </div>

            <div class="venue-grid">

                <?php foreach ($recentVenues as $venue): ?>

                    <article class="venue-card">

                        <h3>
                            <?php echo htmlspecialchars($venue["name"]); ?>
                        </h3>

                        <p>
                            <?php echo htmlspecialchars($venue["location"]); ?>
                        </p>

                        <div class="score">
                            Accessibility score:
                            <strong>
                                <?php echo $venue["score"]; ?>/100
                            </strong>
                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </section>

    </main>

</body>
</html>
