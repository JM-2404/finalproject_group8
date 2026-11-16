
<?php
/**
 * AccessAble — Add Venue
 * Allows administrators to add new venues to the database.
 */

// Load shared functions and verify administrator access.
require_once __DIR__ . '/../includes/functions.php';

// Only administrators can add venues.
if (!isAdmin()) {
    if (isLoggedIn()) {
        setFlash('error', 'You do not have permission to add venues.');
        redirect('dashboard.php');
    }

    setFlash('info', 'Please log in to continue.');
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

// Connect to the shared database.
$pdo = getDB();

// Retrieve categories for the category dropdown.
$categoryQuery = $pdo->query(
    "SELECT id, name FROM categories ORDER BY name ASC"
);

$categories = $categoryQuery->fetchAll();

// Configure the page title and stylesheet.
$pageTitle = 'Add Venue | AccessAble';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>

    <!-- Load the admin panel stylesheet. -->
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>

<div class="admin-layout">

    <!-- Administrator navigation sidebar. -->
    <aside class="admin-sidebar">
        <h2>AccessAble</h2>
        <p class="sidebar-label">ADMINISTRATION</p>

        <nav>
            <a href="index.php">Dashboard</a>
            <a href="venues.php" class="active">Manage Venues</a>
            <a href="reviews.php">Manage Reviews</a>
            <a href="../dashboard.php">User Dashboard</a>
            <a href="../logout.php">Log out</a>
        </nav>
    </aside>

    <main class="admin-main">

        <header class="admin-header">
            <p class="eyebrow">ADMIN PANEL / VENUES</p>
            <h1>Add New Venue</h1>
            <p>Enter the details of a venue at Ashesi University.</p>
        </header>

        <?php displayFlash(); ?>

        <!-- Submit venue details to the processing script. -->
        <section class="reports-section venue-form-section">

            <form
                action="../actions/venue-process.php"
                method="POST"
                enctype="multipart/form-data"
                class="venue-form"
            >

                <!-- Security token to protect the form submission. -->
                <?= csrfField() ?>

                <!-- Tell the processing script which operation to perform. -->
                <input type="hidden" name="action" value="add">

                <div class="venue-form-grid">

                    <!-- Venue name. -->
                    <div class="form-group">
                        <label for="name">Venue name *</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="150"
                            required
                            placeholder="e.g. Main Library"
                        >
                    </div>

                    <!-- Venue category. -->
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">Choose a category</option>

                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>">
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <!-- Venue address. -->
                    <div class="form-group">
                        <label for="address">Address *</label>
                        <input
                            type="text"
                            id="address"
                            name="address"
                            maxlength="255"
                            required
                            placeholder="e.g. Ashesi University Campus"
                        >
                    </div>

                    <!-- Venue city. -->
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input
                            type="text"
                            id="city"
                            name="city"
                            maxlength="100"
                            value="Berekuso"
                            required
                        >
                    </div>

                    <!-- Optional postcode. -->
                    <div class="form-group">
                        <label for="postcode">Postcode</label>
                        <input
                            type="text"
                            id="postcode"
                            name="postcode"
                            maxlength="20"
                            placeholder="Optional"
                        >
                    </div>

                    <!-- Optional contact number. -->
                    <div class="form-group">
                        <label for="phone">Phone number</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            maxlength="40"
                            placeholder="Optional"
                        >
                    </div>

                    <!-- Optional website. -->
                    <div class="form-group">
                        <label for="website">Website</label>
                        <input
                            type="url"
                            id="website"
                            name="website"
                            maxlength="255"
                            placeholder="https://example.com"
                        >
                    </div>

                    <!-- Optional opening hours. -->
                    <div class="form-group">
                        <label for="opening_hours">Opening hours</label>
                        <input
                            type="text"
                            id="opening_hours"
                            name="opening_hours"
                            maxlength="255"
                            placeholder="e.g. Monday - Friday, 8 AM - 5 PM"
                        >
                    </div>

                    <!-- Optional venue image upload. -->
                    <div class="form-group full-width">
                        <label for="image">Venue image</label>
                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp"
                        >
                        <small>Accepted formats: JPG, PNG and WebP. Maximum size: 2 MB.</small>
                    </div>

                    <!-- Venue description. -->
                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                            placeholder="Describe the venue and its facilities..."
                        ></textarea>
                    </div>

                    <!-- Mark the venue as featured if appropriate. -->
                    <div class="form-group full-width checkbox-group">
                        <label>
                            <input
                                type="checkbox"
                                name="is_featured"
                                value="1"
                            >
                            Feature this venue on the Explore page
                        </label>
                    </div>

                </div>

                <!-- Form actions. -->
                <div class="venue-form-actions">
                    <a href="venues.php" class="cancel-button">Cancel</a>

                    <button type="submit" class="btn btn-primary">
                        Save Venue
                    </button>
                </div>

            </form>

        </section>

    </main>
</div>

</body>
</html>
