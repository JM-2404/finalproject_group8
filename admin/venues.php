
<?php

// ============================================================
// 1. LOAD SHARED FUNCTIONS AND CHECK ADMIN ACCESS
// ============================================================

// Load the shared database, session and application functions.
require_once __DIR__ . '/../includes/functions.php';

// Only administrators can manage venues.
if (!isAdmin()) {

    // Redirect logged-in ordinary users to their dashboard.
    if (isLoggedIn()) {
        setFlash('error', 'You do not have permission to manage venues.');
        redirect('dashboard.php');
    }

    // Redirect visitors who are not logged in.
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

// Get the shared database connection.
$pdo = getDB();


// ============================================================
// 2. HANDLE VENUE ACTIVATION AND DEACTIVATION
// ============================================================

// Process status changes only when a form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verify the POST request and its CSRF token.
    requirePostWithCsrf('admin/venues.php');

    // Retrieve and validate the submitted venue ID.
    $venueId = filter_var(
        $_POST['venue_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    // Retrieve the requested active status.
    $newStatus = $_POST['new_status'] ?? '';

    // Only allow the two valid status values.
    if ($venueId === false || !in_array($newStatus, ['0', '1'], true)) {

        setFlash('error', 'Invalid venue status request.');

    } else {

        // Update only the selected venue.
        $updateQuery = $pdo->prepare(
            "UPDATE venues
             SET is_active = :status
             WHERE id = :venue_id"
        );

        $updateQuery->execute([
            'status' => (int) $newStatus,
            'venue_id' => $venueId
        ]);

        // Display a message describing the action.
        if ($updateQuery->rowCount() > 0) {
            setFlash(
                'success',
                $newStatus === '1'
                    ? 'Venue activated successfully.'
                    : 'Venue deactivated successfully.'
            );
        } else {
            setFlash('info', 'No venue status changes were made.');
        }
    }

    // Redirect to prevent accidental form resubmission.
    redirect('admin/venues.php');
}


// ============================================================
// 3. HANDLE SEARCH AND STATUS FILTERS
// ============================================================

// Retrieve the search term entered by the administrator.
$search = trim($_GET['search'] ?? '');

// Retrieve the selected status filter.
// The default is to display all venues.
$statusFilter = $_GET['status'] ?? 'all';

// Build the query conditions and parameters safely.
$whereConditions = [];
$queryParams = [];

// Search the venue name, address and city.
if ($search !== '') {

    $whereConditions[] = "
        (
            v.name LIKE :search_name
            OR v.address LIKE :search_address
            OR v.city LIKE :search_city
        )
    ";

    // Use separate named parameters for each search condition.
    $searchValue = '%' . $search . '%';

    $queryParams['search_name'] = $searchValue;
    $queryParams['search_address'] = $searchValue;
    $queryParams['search_city'] = $searchValue;
}

// Filter by active or inactive status.
if ($statusFilter === 'active') {

    $whereConditions[] = 'v.is_active = 1';

} elseif ($statusFilter === 'inactive') {

    $whereConditions[] = 'v.is_active = 0';
}

// Combine the conditions when filters are selected.
$whereSQL = '';

if (!empty($whereConditions)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereConditions);
}


// ============================================================
// 4. RETRIEVE VENUES FROM THE DATABASE
// ============================================================

// Join categories so each venue displays its category name.
$venueSQL = "
    SELECT
        v.id,
        v.name,
        v.address,
        v.city,
        v.is_active,
        c.name AS category_name
    FROM venues v
    LEFT JOIN categories c ON c.id = v.category_id
    $whereSQL
    ORDER BY v.name ASC
";

// Prepare and execute the database query.
$venueQuery = $pdo->prepare($venueSQL);
$venueQuery->execute($queryParams);

// Retrieve all matching venues.
$venues = $venueQuery->fetchAll();


// ============================================================
// 5. RETRIEVE TOTAL VENUE COUNT
// ============================================================

// Count all active and inactive venues for the page summary.
$countQuery = $pdo->query(
    "SELECT COUNT(*) FROM venues"
);

$totalVenues = (int) $countQuery->fetchColumn();

// Set the page title.
$pageTitle = 'Manage Venues | AccessAble';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <!-- Set character encoding and responsive viewport. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Set the browser tab title. -->
    <title><?php echo e($pageTitle); ?></title>

    <!-- Load the existing admin stylesheet. -->
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

        <nav>
            <a href="index.php">Dashboard</a>
            <a href="venues.php" class="active">Manage Venues</a>
            <a href="reviews.php">Manage Reviews</a>
            <a href="../dashboard.php">User Dashboard</a>
            <a href="../logout.php">Log out</a>
        </nav>

    </aside>


    <!-- ======================================================
         7. MAIN VENUE MANAGEMENT CONTENT
         ====================================================== -->

    <main class="admin-main">

        <header class="admin-header">

            <div>
                <p class="eyebrow">ADMIN PANEL</p>

                <h1>Manage Venues</h1>

                <p>
                    View, search and manage accessibility venues
                    across Ashesi University.
                </p>
            </div>

        </header>


        <!-- Display success and error messages. -->
        <?php displayFlash(); ?>


        <!-- ==================================================
             8. VENUE SUMMARY AND ADD BUTTON
             ================================================== -->

        <section class="admin-stats">

            <article class="admin-stat-card">
                <h2><?php echo $totalVenues; ?></h2>
                <p>Total venues</p>
            </article>

            <article class="admin-stat-card">
                <h2><?php echo count($venues); ?></h2>
                <p>Matching venues</p>
            </article>

        </section>

        <!-- Link to the page for creating a new venue. -->
        <div class="venue-actions">
            <a href="add-venue.php" class="btn btn-primary">
                + Add New Venue
            </a>
        </div>


        <!-- ==================================================
             9. SEARCH AND FILTER FORM
             ================================================== -->

        <section class="reports-section">

            <h2>All Venues</h2>

            <form method="GET" action="venues.php" class="venue-filter-form">

                <!-- Search by venue name, address or city. -->
                <input
                    type="search"
                    name="search"
                    placeholder="Search venues..."
                    value="<?php echo e($search); ?>"
                    aria-label="Search venues"
                >

                <!-- Filter venues by their active status. -->
                <select name="status" aria-label="Filter by status">

                    <option value="all"
                        <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>
                        All venues
                    </option>

                    <option value="active"
                        <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>
                        Active venues
                    </option>

                    <option value="inactive"
                        <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>
                        Inactive venues
                    </option>

                </select>

                <!-- Submit the selected search and filter options. -->
                <button type="submit" class="btn btn-primary">
                    Search
                </button>

                <!-- Clear all filters. -->
                <a href="venues.php">Clear filters</a>

            </form>


            <!-- ==================================================
                 10. VENUES TABLE
                 ================================================== -->

            <div class="reports-table-wrapper">

                <table>

                    <thead>
                        <tr>
                            <th>Venue</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!empty($venues)): ?>

                            <!-- Display each venue retrieved from the database. -->
                            <?php foreach ($venues as $venue): ?>

                                <tr>

                                    <!-- Display the venue name. -->
                                    <td>
                                        <strong>
                                            <?php echo e($venue['name']); ?>
                                        </strong>
                                    </td>

                                    <!-- Display the venue category. -->
                                    <td>
                                        <?php echo e($venue['category_name'] ?? 'Uncategorised'); ?>
                                    </td>

                                    <!-- Display the venue location. -->
                                    <td>
                                        <?php echo e($venue['address']); ?>,
                                        <?php echo e($venue['city']); ?>
                                    </td>

                                    <!-- Display whether the venue is active. -->
                                    <td>

                                        <span class="status">
                                            <?php echo $venue['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>

                                    </td>

                                    <!-- Provide editing and status management actions. -->
                                    <td>

                                        <a href="edit-venue.php?id=<?php echo (int) $venue['id']; ?>">
                                            Edit
                                        </a>

                                        <!-- Submit a secure request to change venue status. -->
                                        <form method="POST" action="venues.php" class="venue-status-form">

                                            <!-- Include the CSRF security token. -->
                                            <?php echo csrfField(); ?>

                                            <!-- Identify the venue being updated. -->
                                            <input
                                                type="hidden"
                                                name="venue_id"
                                                value="<?php echo (int) $venue['id']; ?>"
                                            >

                                            <!-- Toggle between active and inactive. -->
                                            <input
                                                type="hidden"
                                                name="new_status"
                                                value="<?php echo $venue['is_active'] ? '0' : '1'; ?>"
                                            >

                                            <button type="submit">

                                                <?php echo $venue['is_active'] ? 'Deactivate' : 'Activate'; ?>

                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <!-- Display a message when no venues match the filters. -->
                            <tr>
                                <td colspan="5">
                                    No venues found.
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
