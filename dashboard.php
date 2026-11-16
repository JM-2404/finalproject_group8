
<?php

// ============================================================
// 1. AUTHENTICATION
// ============================================================

// Protect this page so only logged-in users can access it.
// This file also loads functions.php and starts the session.
require_once __DIR__ . '/includes/auth-check.php';

// Retrieve the currently logged-in user's information.
$user = getCurrentUser();

// Get the user's name for the welcome message.
// The fallback prevents an error if the name is unavailable.
$userName = $user['full_name'] ?? 'Student';

// Get the ID of the currently logged-in user.
$userId = currentUserId();


// ============================================================
// 2. RETRIEVE USER DATA FROM THE DATABASE
// ============================================================

// Retrieve all venues saved by this user.
$favourites = getUserFavourites($userId);

// Retrieve all reviews submitted by this user.
$reviews = getUserReviews($userId);

// Retrieve the user's recently viewed venues.
$recentVenues = getRecentlyViewed($userId);


// ============================================================
// 3. CALCULATE DASHBOARD STATISTICS
// ============================================================

// Count the records returned by each database function.
$dashboardStats = [
    'favourites' => count($favourites),
    'reviews' => count($reviews),
    'recently_viewed' => count($recentVenues)
];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Set the character encoding and responsive viewport. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Set the browser tab title. -->
    <title>My Dashboard | AccessAble</title>

    <!-- Load the dashboard stylesheet. -->
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>

    <!-- Main dashboard content. -->
    <main class="dashboard">

        <!-- ==================================================
             4. DASHBOARD HEADER
             ================================================== -->

        <header class="dashboard-header">

            <div>
                <p class="eyebrow">MY DASHBOARD</p>

                <!-- Display the actual logged-in user's name. -->
                <h1>Welcome, <?php echo e($userName); ?>!</h1>

                <p>
                    Keep track of your favourite places
                    around Ashesi University.
                </p>
            </div>

            <!-- Link to the venue exploration page. -->
            <a href="explore.php" class="explore-button">
                Explore venues
            </a>

        </header>


        <!-- ==================================================
             5. DASHBOARD STATISTICS
             ================================================== -->

        <section class="stats-grid">

            <!-- Display the number of saved venues. -->
            <div class="stat-card">
                <h2><?php echo $dashboardStats['favourites']; ?></h2>
                <p>Saved venues</p>
            </div>

            <!-- Display the number of reviews submitted. -->
            <div class="stat-card">
                <h2><?php echo $dashboardStats['reviews']; ?></h2>
                <p>Reviews submitted</p>
            </div>

            <!-- Display the number of recently viewed venues. -->
            <div class="stat-card">
                <h2><?php echo $dashboardStats['recently_viewed']; ?></h2>
                <p>Recently viewed</p>
            </div>

        </section>


        <!-- ==================================================
             6. RECENTLY VIEWED VENUES
             ================================================== -->

        <section class="recent-section">

            <div class="section-heading">

                <div>
                    <h2>Recently viewed venues</h2>
                    <p>Your recently explored campus locations.</p>
                </div>

                <!-- Link to the user's favourites page. -->
                <a href="favourites.php">View favourites</a>

            </div>

            <div class="venue-grid">

                <?php if (!empty($recentVenues)): ?>

                    <!-- Loop through the recently viewed venues. -->
                    <?php foreach ($recentVenues as $venue): ?>

                        <article class="venue-card">

                            <!-- Display the venue name as a link. -->
                            <h3>
                                <a href="<?php echo e(venueUrl((int) $venue['id'])); ?>">
                                    <?php echo e($venue['name']); ?>
                                </a>
                            </h3>

                            <!-- Display the venue's city. -->
                            <p>
                                <?php echo e($venue['city']); ?>
                            </p>

                            <!-- Display the calculated accessibility score. -->
                            <div class="score">
                                Accessibility score:

                                <strong>
                                    <?php echo (int) $venue['accessibility_score']; ?>/100
                                </strong>
                            </div>

                        </article>

                    <?php endforeach; ?>

                <?php else: ?>

                    <!-- Show this message when there are no recently viewed venues. -->
                    <p>
                        You haven't viewed any venues yet.
                        Explore Ashesi to get started!
                    </p>

                <?php endif; ?>

            </div>

        </section>

    </main>

</body>
</html>
