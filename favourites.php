
<?php
/**
 * AccessAble — User Favourites
 * Displays the logged-in user's saved venues from the database.
 */

// Protect this page and load the shared database functions.
require_once __DIR__ . '/includes/auth-check.php';

// Get the logged-in user's ID.
$userId = currentUserId();

// Retrieve this user's saved venues from the database.
$favourites = getUserFavourites($userId);

// Configure the shared page header.
$pageTitle = 'My Favourites | AccessAble';
$pageStyles = ['css/favourites.css'];

// Load the shared header and navigation.
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main id="main-content" class="favourites-page">

    <header class="favourites-header">
        <p class="eyebrow">YOUR SAVED PLACES</p>
        <h1>My Favourite Venues</h1>
        <p>Keep track of places you want to visit around Ashesi University.</p>
    </header>

    <?php displayFlash(); ?>

    <!-- Search field for filtering saved venues. -->
    <input
        type="search"
        id="favourite-search"
        class="favourites-search"
        placeholder="Search your favourite venues..."
        aria-label="Search favourite venues"
    >

    <section class="favourites-grid" id="favourites-grid">

        <?php if (empty($favourites)): ?>

            <!-- Show a helpful message when the user has no saved venues. -->
            <div class="empty-favourites">
                <h2>No favourites yet</h2>
                <p>You haven't saved any venues. Explore Ashesi and save places you like!</p>
                <a href="explore.php" class="btn btn-primary">Explore venues</a>
            </div>

        <?php else: ?>

            <?php foreach ($favourites as $venue): ?>

                <article class="favourite-card">

                    <div class="favourite-card-content">

                        <p class="favourite-category">
                            <?= e($venue['category_name'] ?? 'Campus venue') ?>
                        </p>

                        <h2>
                            <?= e($venue['name']) ?>
                        </h2>

                        <p class="favourite-location">
                            <?= e($venue['address']) ?>,
                            <?= e($venue['city']) ?>
                        </p>

                        <div class="favourite-score">
                            Accessibility score:
                            <strong>
                                <?= (int) $venue['accessibility_score'] ?>/100
                            </strong>
                        </div>

                        <?php if ($venue['avg_rating'] !== null): ?>
                            <p class="favourite-rating">
                                Average rating:
                                <?= e($venue['avg_rating']) ?>/5
                            </p>
                        <?php endif; ?>

                    </div>

                    <!-- Remove this venue from the logged-in user's favourites. -->
                    <form
                        action="actions/favourite-process.php"
                        method="POST"
                        class="remove-favourite-form"
                    >
                        <?= csrfField() ?>

                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="venue_id" value="<?= (int) $venue['id'] ?>">

                        <button type="submit" class="remove-favourite">
                            Remove from favourites
                        </button>
                    </form>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</main>

<!-- Load the search functionality. -->
<script src="js/favourites.js" defer></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
