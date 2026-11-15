
<?php

session_start();

// Temporary demonstration data
if (!isset($_SESSION['favourites'])) {
    $_SESSION['favourites'] = [
        [
            "id" => 1,
            "name" => "Main University Building",
            "location" => "Ashesi University",
            "score" => 85
        ],
        [
            "id" => 2,
            "name" => "Library",
            "location" => "Ashesi University",
            "score" => 90
        ],
        [
            "id" => 3,
            "name" => "Student Centre",
            "location" => "Ashesi University",
            "score" => 75
        ]
    ];
}

// Remove a favourite
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['remove_id'])) {

    $removeId = (int) $_POST['remove_id'];

    $_SESSION['favourites'] = array_values(
        array_filter(
            $_SESSION['favourites'],
            fn($venue) => $venue['id'] !== $removeId
        )
    );

    header("Location: favourites.php");
    exit;
}

$favourites = $_SESSION['favourites'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Favourites | AccessAble</title>

    <link rel="stylesheet" href="css/favourites.css">
</head>

<body>

<main class="favourites-page">

    <header class="favourites-header">

        <h1>My Favourite Venues</h1>

        <p>
            Keep track of places you want to visit
            around Ashesi University.
        </p>

    </header>

    <input
        type="search"
        id="favourite-search"
        class="favourites-search"
        placeholder="Search your favourite venues..."
        aria-label="Search favourite venues"
    >

    <section class="favourites-grid" id="favourites-grid">

        <?php if (empty($favourites)): ?>

            <div class="empty-favourites">
                <h2>No favourites yet</h2>
                <p>You haven't saved any venues.</p>
            </div>

        <?php else: ?>

            <?php foreach ($favourites as $venue): ?>

                <article class="favourite-card">

                    <h2>
                        <?= htmlspecialchars($venue['name']); ?>
                    </h2>

                    <p>
                        <?= htmlspecialchars($venue['location']); ?>
                    </p>

                    <div class="favourite-score">
                        Accessibility score:
                        <?= $venue['score']; ?>/100
                    </div>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="remove_id"
                            value="<?= $venue['id']; ?>"
                        >

                        <button
                            type="submit"
                            class="remove-favourite"
                        >
                            Remove from favourites
                        </button>

                    </form>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</main>

<script src="js/favourites.js"></script>

</body>
</html>
