<?php
/**
 * AccessAble — Venue Processing
 * Handles adding new venues, editing existing venues and uploading images.
 */

// Load shared functions and verify administrator access.
require_once __DIR__ . '/../includes/auth-check.php';

// Only accept POST requests with a valid CSRF token.
requirePostWithCsrf('admin/venues.php');

// Get the administrator ID and database connection.
$adminId = currentUserId();
$pdo = getDB();

// Check whether this is an edit request or a new venue request.
$venueId = filter_input(INPUT_POST, 'venue_id', FILTER_VALIDATE_INT);
$isEditing = $venueId !== null && $venueId !== false && $venueId > 0;

// Choose the correct page to return to if validation fails.
$redirectBack = $isEditing
    ? 'admin/edit-venue.php?id=' . $venueId
    : 'admin/add-venue.php';

// Read and clean the submitted form values.
$name = trim($_POST['name'] ?? '');
$categoryInput = $_POST['category_id'] ?? '';
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$postcode = trim($_POST['postcode'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$website = trim($_POST['website'] ?? '');
$openingHours = trim($_POST['opening_hours'] ?? '');
$description = trim($_POST['description'] ?? '');
$isFeatured = isset($_POST['is_featured']) ? 1 : 0;

// Check that the required fields are present and within their limits.
if (
    $name === '' ||
    $address === '' ||
    $city === '' ||
    mb_strlen($name) > 150 ||
    mb_strlen($address) > 255 ||
    mb_strlen($city) > 100 ||
    mb_strlen($postcode) > 20 ||
    mb_strlen($phone) > 40 ||
    mb_strlen($website) > 255 ||
    mb_strlen($openingHours) > 255
) {
    setFlash('error', 'Please complete the required fields and check the field lengths.');
    redirect($redirectBack);
}

// Validate the optional website address.
if ($website !== '' && filter_var($website, FILTER_VALIDATE_URL) === false) {
    setFlash('error', 'Please enter a valid website URL.');
    redirect($redirectBack);
}

// Confirm that the venue exists when processing an edit request.
$existingVenue = null;

if ($isEditing) {
    $existingQuery = $pdo->prepare(
        "SELECT id, slug, image FROM venues WHERE id = :id LIMIT 1"
    );
    $existingQuery->execute(['id' => $venueId]);
    $existingVenue = $existingQuery->fetch(PDO::FETCH_ASSOC);

    if (!$existingVenue) {
        setFlash('error', 'The venue you are trying to edit does not exist.');
        redirect('admin/venues.php');
    }
}

// Validate the selected category.
$categoryId = null;

if ($categoryInput !== '') {
    $categoryId = filter_var(
        $categoryInput,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($categoryId === false) {
        setFlash('error', 'Please select a valid category.');
        redirect($redirectBack);
    }

    // Confirm that the selected category exists.
    $categoryCheck = $pdo->prepare(
        "SELECT id FROM categories WHERE id = :category_id"
    );
    $categoryCheck->execute(['category_id' => $categoryId]);

    if (!$categoryCheck->fetch()) {
        setFlash('error', 'The selected category does not exist.');
        redirect($redirectBack);
    }
}

// Generate a URL-friendly slug from the venue name.
$baseSlug = strtolower(trim($name));
$baseSlug = preg_replace('/[^a-z0-9]+/', '-', $baseSlug);
$baseSlug = trim($baseSlug, '-');

if ($baseSlug === '') {
    setFlash('error', 'Please enter a venue name containing letters or numbers.');
    redirect($redirectBack);
}

// Check that the slug is unique, excluding this venue when editing.
$slug = $baseSlug;
$suffix = 2;

while (true) {
    if ($isEditing) {
        $slugCheck = $pdo->prepare(
            "SELECT id FROM venues
             WHERE slug = :slug AND id != :venue_id
             LIMIT 1"
        );

        $slugCheck->execute([
            'slug' => $slug,
            'venue_id' => $venueId
        ]);
    } else {
        $slugCheck = $pdo->prepare(
            "SELECT id FROM venues WHERE slug = :slug LIMIT 1"
        );

        $slugCheck->execute(['slug' => $slug]);
    }

    if (!$slugCheck->fetch()) {
        break;
    }

    $slug = $baseSlug . '-' . $suffix;
    $suffix++;
}

// Keep the existing image unless a new image is uploaded.
$imagePath = $isEditing ? $existingVenue['image'] : null;
$uploadedImagePath = null;

// Validate and save a newly uploaded image, if one was provided.
if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {

    // Check whether the upload completed successfully.
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        setFlash('error', 'The image upload failed. Please try again.');
        redirect($redirectBack);
    }

    // Enforce the 2 MB upload limit.
    if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
        setFlash('error', 'The image must not be larger than 2 MB.');
        redirect($redirectBack);
    }

    // Detect the actual file type rather than trusting its filename.
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($_FILES['image']['tmp_name']);

    // Only allow JPG, PNG and WebP images.
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedTypes[$mimeType])) {
        setFlash('error', 'Only JPG, PNG and WebP images are allowed.');
        redirect($redirectBack);
    }

    // Create a unique filename for the uploaded image.
    $imageFileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];

    // Define the directory where venue images are stored.
    $uploadDirectory = __DIR__ . '/../uploads/venues/';

    // Create the directory if it does not already exist.
    if (
        !is_dir($uploadDirectory) &&
        !mkdir($uploadDirectory, 0755, true) &&
        !is_dir($uploadDirectory)
    ) {
        setFlash('error', 'The image upload directory could not be created.');
        redirect($redirectBack);
    }

    // Move the uploaded image to its permanent location.
    $destination = $uploadDirectory . $imageFileName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
        setFlash('error', 'Could not save the uploaded image.');
        redirect($redirectBack);
    }

    // Save the relative path for the database.
    $imagePath = 'uploads/venues/' . $imageFileName;
    $uploadedImagePath = $destination;
}

try {

    if ($isEditing) {

        // Update the existing venue's information.
        $saveVenue = $pdo->prepare(
            "UPDATE venues SET
                name = :name,
                slug = :slug,
                category_id = :category_id,
                description = :description,
                address = :address,
                city = :city,
                postcode = :postcode,
                phone = :phone,
                website = :website,
                opening_hours = :opening_hours,
                image = :image,
                is_featured = :is_featured
             WHERE id = :id"
        );

        $saveVenue->execute([
            'name' => $name,
            'slug' => $slug,
            'category_id' => $categoryId,
            'description' => $description !== '' ? $description : null,
            'address' => $address,
            'city' => $city,
            'postcode' => $postcode !== '' ? $postcode : null,
            'phone' => $phone !== '' ? $phone : null,
            'website' => $website !== '' ? $website : null,
            'opening_hours' => $openingHours !== '' ? $openingHours : null,
            'image' => $imagePath,
            'is_featured' => $isFeatured,
            'id' => $venueId
        ]);

        // Remove the previous image only after the update succeeds.
        if (
            $uploadedImagePath !== null &&
            !empty($existingVenue['image'])
        ) {
            $oldImagePath = __DIR__ . '/../' . $existingVenue['image'];

            if (is_file($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        setFlash('success', 'Venue updated successfully.');

    } else {

        // Insert a new venue into the database.
        $saveVenue = $pdo->prepare(
            "INSERT INTO venues (
                name,
                slug,
                category_id,
                description,
                address,
                city,
                postcode,
                phone,
                website,
                opening_hours,
                image,
                is_featured,
                is_active,
                created_by
            ) VALUES (
                :name,
                :slug,
                :category_id,
                :description,
                :address,
                :city,
                :postcode,
                :phone,
                :website,
                :opening_hours,
                :image,
                :is_featured,
                1,
                :created_by
            )"
        );

        $saveVenue->execute([
            'name' => $name,
            'slug' => $slug,
            'category_id' => $categoryId,
            'description' => $description !== '' ? $description : null,
            'address' => $address,
            'city' => $city,
            'postcode' => $postcode !== '' ? $postcode : null,
            'phone' => $phone !== '' ? $phone : null,
            'website' => $website !== '' ? $website : null,
            'opening_hours' => $openingHours !== '' ? $openingHours : null,
            'image' => $imagePath,
            'is_featured' => $isFeatured,
            'created_by' => $adminId
        ]);

        setFlash('success', 'Venue added successfully.');
    }

    // Return to the venue management page after saving.
    redirect('admin/venues.php');

} catch (PDOException $e) {

    // Remove a newly uploaded image if saving to the database failed.
    if ($uploadedImagePath !== null && is_file($uploadedImagePath)) {
        unlink($uploadedImagePath);
    }

    // Log the technical error for debugging.
    error_log('AccessAble venue save failed: ' . $e->getMessage());

    // Display a safe message to the administrator.
    setFlash('error', 'The venue could not be saved. Please try again.');
    redirect($redirectBack);
}