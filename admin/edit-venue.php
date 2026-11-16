<?php
/**
 * AccessAble — Edit Venue
 * Allows administrators to update an existing venue.
 */

// Load shared functions and check administrator access.
require_once __DIR__ . '/../includes/functions.php';

// Prevent non-administrators from accessing this page.
if (!isAdmin()) {
    redirect(isLoggedIn() ? 'dashboard.php' : 'login.php');
}

// Get the database connection.
$pdo = getDB();

// Read the venue ID from the URL.
$venueId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Reject invalid venue IDs.
if (!$venueId || $venueId < 1) {
    setFlash('error', 'Invalid venue selected.');
    redirect('admin/venues.php');
}

// Retrieve the venue being edited.
$venueQuery = $pdo->prepare(
    "SELECT * FROM venues WHERE id = :id LIMIT 1"
);
$venueQuery->execute(['id' => $venueId]);
$venue = $venueQuery->fetch(PDO::FETCH_ASSOC);

// Return to the venue list if the venue does not exist.
if (!$venue) {
    setFlash('error', 'Venue not found.');
    redirect('admin/venues.php');
}

// Retrieve categories for the category dropdown.
$categoryQuery = $pdo->query(
    "SELECT id, name FROM categories ORDER BY name ASC"
);
$categories = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

// Display any success or error messages.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Venue | AccessAble Admin</title>

    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>

    <main class="admin-main">
        <section class="venue-form-section">

            <h1>Edit Venue</h1>
            <p>Update the information for this venue.</p>

            <?php displayFlash(); ?>

            <!-- Submit the updated venue details to the processing script. -->
            <form
                action="../actions/venue-process.php"
                method="POST"
                enctype="multipart/form-data"
                class="venue-form"
            >

                <!-- Identify this as an edit request and include the venue ID. -->
                <?php echo csrfField(); ?>
                <input type="hidden" name="venue_id"
                       value="<?php echo (int) $venue['id']; ?>">

                <div class="venue-form-grid">

                    <div class="form-group">
                        <label for="name">Venue Name *</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="150"
                            value="<?php echo e($venue['name']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">Select a category</option>

                            <?php foreach ($categories as $category): ?>
                                <option
                                    value="<?php echo (int) $category['id']; ?>"
                                    <?php echo (int) $venue['category_id'] === (int) $category['id'] ? 'selected' : ''; ?>
                                >
                                    <?php echo e($category['name']); ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="address">Address *</label>
                        <input
                            type="text"
                            id="address"
                            name="address"
                            maxlength="255"
                            value="<?php echo e($venue['address']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="city">City *</label>
                        <input
                            type="text"
                            id="city"
                            name="city"
                            maxlength="100"
                            value="<?php echo e($venue['city']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="postcode">Postcode</label>
                        <input
                            type="text"
                            id="postcode"
                            name="postcode"
                            maxlength="20"
                            value="<?php echo e($venue['postcode'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input
                            type="text"
                            id="phone"
                            name="phone"
                            maxlength="40"
                            value="<?php echo e($venue['phone'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="website">Website</label>
                        <input
                            type="url"
                            id="website"
                            name="website"
                            maxlength="255"
                            value="<?php echo e($venue['website'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-group full-width">
                        <label for="opening_hours">Opening Hours</label>
                        <input
                            type="text"
                            id="opening_hours"
                            name="opening_hours"
                            maxlength="255"
                            value="<?php echo e($venue['opening_hours'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="5"
                        ><?php echo e($venue['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label for="image">Replace Venue Image</label>

                        <?php if (!empty($venue['image'])): ?>
                            <p>Current image:</p>
                            <img
                                src="../<?php echo e($venue['image']); ?>"
                                alt="Current image of <?php echo e($venue['name']); ?>"
                                style="max-width: 180px; height: auto; margin-bottom: 12px;"
                            >
                        <?php endif; ?>

                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                        <small>Leave this empty to keep the current image. Maximum size: 2 MB.</small>
                    </div>

                    <div class="form-group full-width">
                        <label>
                            <input
                                type="checkbox"
                                name="is_featured"
                                value="1"
                                <?php echo $venue['is_featured'] ? 'checked' : ''; ?>
                            >
                            Feature this venue
                        </label>
                    </div>

                </div>

                <div class="venue-form-actions">
                    <a href="venues.php" class="cancel-button">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>

            </form>
        </section>
    </main>

</body>
</html>