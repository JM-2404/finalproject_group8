<?php
/**
 * AccessAble — Shared core functions
 * Owner: Member 4 (declared once here, reused by every page)
 *
 * Include at the top of every page BEFORE includes/header.php:
 *   require_once __DIR__ . '/includes/functions.php';        // pages in the root
 *   require_once __DIR__ . '/../includes/functions.php';     // pages in actions/ or admin/
 *
 * This file starts the session and opens the database connection, so pages
 * do not need to call session_start() or include config/database.php themselves.
 *
 * Contents
 *   1. Session & output helpers   e(), url(), redirect(), flash messages, old input
 *   2. Security                   CSRF tokens, safe redirect targets
 *   3. Authentication             isLoggedIn(), isAdmin(), currentUserId(), getCurrentUser(), loginUser()
 *   4. Venues & features          getAllVenues(), getVenueById(), getCategories(), getFeatures()
 *   5. Scoring                    calculateAccessibilityScore(), getScoreBreakdown(), getScoreLabel()
 *   6. Reviews                    getVenueReviews(), getUserReview(), getUserReviews()
 *   7. Favourites & preferences   getUserFavourites(), addFavourite(), removeFavourite(), isFavourite(), ...
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';

/* =========================================================================
 * 1. SESSION & OUTPUT HELPERS
 * ========================================================================= */

/** Escape a value for safe HTML output. Use for EVERY value printed from the DB or user input. */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Build a link to a file in the project root that works from any folder
 * (root pages, actions/ and admin/), on XAMPP sub-folders and on the live host.
 *   url('login.php')  → 'login.php' from the root, '../login.php' from actions/
 */
function url(string $path = ''): string
{
    static $prefix = null;
    if ($prefix === null) {
        $prefix = '';
        $root   = realpath(dirname(__DIR__));
        $script = isset($_SERVER['SCRIPT_FILENAME']) ? realpath($_SERVER['SCRIPT_FILENAME']) : false;
        if ($root && $script && strpos($script, $root) === 0) {
            $relativeDir = trim(str_replace('\\', '/', substr(dirname($script), strlen($root))), '/');
            if ($relativeDir !== '') {
                $prefix = str_repeat('../', substr_count($relativeDir, '/') + 1);
            }
        }
    }
    return $prefix . ltrim($path, '/');
}

/** Redirect to a project path (see url()) and stop. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** True if a project page exists (used while teammates' pages are still being built). */
function pageExists(string $path): bool
{
    $file = strtok($path, '?#');
    return is_file(dirname(__DIR__) . '/' . ltrim((string) $file, '/'));
}

/** Where to send a user after login/registration: admin area, dashboard, or the homepage. */
function landingPage(bool $admin = false): string
{
    if ($admin && pageExists('admin/index.php')) {
        return 'admin/index.php';
    }
    return pageExists('dashboard.php') ? 'dashboard.php' : 'index.php';
}

/** Link to a venue's details page, falling back to Explore until venue-details.php exists. */
function venueUrl(int $venueId, string $anchor = ''): string
{
    return pageExists('venue-details.php')
        ? 'venue-details.php?id=' . $venueId . ($anchor !== '' ? '#' . $anchor : '')
        : 'explore.php';
}

/** Store a one-time message shown on the next page. $type: success | error | info | warning */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Return and clear all flash messages. */
function getFlash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Print flash messages as accessible alert boxes (call once inside <main>). */
function displayFlash(): void
{
    foreach (getFlash() as $flash) {
        $type = in_array($flash['type'], ['success', 'error', 'info', 'warning'], true) ? $flash['type'] : 'info';
        $role = $type === 'error' ? 'alert' : 'status';
        echo '<div class="flash flash-' . $type . '" role="' . $role . '">' . e($flash['message']) . '</div>';
    }
}

/** Remember submitted form values (never passwords) so the form can be refilled after an error. */
function setOldInput(array $data): void
{
    unset($data['password'], $data['confirm_password'], $data['csrf_token']);
    $_SESSION['old_input'] = $data;
}

/** Read a remembered form value. */
function old(string $key, string $default = ''): string
{
    return isset($_SESSION['old_input'][$key]) ? (string) $_SESSION['old_input'][$key] : $default;
}

function clearOldInput(): void
{
    unset($_SESSION['old_input']);
}

/** Store field-level validation errors for the next page load. */
function setFormErrors(array $errors): void
{
    $_SESSION['form_errors'] = $errors;
}

/** Return field-level errors (and clear them). */
function getFormErrors(): array
{
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['form_errors']);
    return $errors;
}

/* =========================================================================
 * 2. SECURITY
 * ========================================================================= */

/** Get (or create) this session's CSRF token. */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input to place inside every POST form. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/** True when the submitted token matches the session token. */
function verifyCsrf(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Only allow redirects to local project pages (prevents open-redirect attacks). */
function safeRedirectTarget(?string $target, ?string $fallback = null): string
{
    $fallback = $fallback ?? landingPage();
    $target   = trim((string) $target);
    if ($target === ''
        || preg_match('#^[a-z][a-z0-9+.-]*:#i', $target)  // http:, javascript:, ...
        || strpos($target, '//') !== false
        || strpos($target, '\\') !== false
        || strpos($target, '..') !== false
        || $target[0] === '/') {
        return $fallback;
    }
    return preg_match('#^[A-Za-z0-9_\-/]+\.php([?\#].*)?$#', $target) ? $target : $fallback;
}

/** Require a POST request with a valid CSRF token, otherwise bounce back. */
function requirePostWithCsrf(string $backTo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect($backTo);
    }
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect($backTo);
    }
}

/* =========================================================================
 * 3. AUTHENTICATION
 * ========================================================================= */

// isLoggedIn(), isAdmin() and currentUserId() are defined in includes/session.php

/** Full row for the logged-in user (without the password hash), or null. */
function getCurrentUser(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    if (!isLoggedIn()) {
        return $user = null;
    }
    $stmt = getDB()->prepare(
        'SELECT id, full_name, email, role, created_at, last_login_at
           FROM users WHERE id = ? AND is_active = 1'
    );
    $stmt->execute([currentUserId()]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

/** Find a user by email (includes password_hash — for login only). */
function getUserByEmail(string $email): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    return $stmt->fetch() ?: null;
}

/** Log a user in: new session id (prevents session fixation) and session values. */
function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']    = (int) $user['id'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['login_time'] = time();
    unset($_SESSION['csrf_token']); // fresh token after privilege change

    getDB()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
}

/* =========================================================================
 * 4. VENUES, CATEGORIES & FEATURES
 * ========================================================================= */

/** SQL fragment that computes the 0–100 score for venue alias v. */
function scoreSqlExpression(): string
{
    return '(SELECT COALESCE(SUM(f.weight), 0)
               FROM venue_features vf
               JOIN features f ON f.id = vf.feature_id
              WHERE vf.venue_id = v.id AND vf.status = \'available\')';
}

/**
 * Retrieve active venues for search and display.
 *
 * Every row includes: id, name, slug, parent_id, parent_name, category_id, category_name,
 * category_slug, description, address, city, image, is_featured, accessibility_score,
 * avg_rating, review_count, created_at.
 *
 * Optional $filters (all can be combined):
 *   'search'    => text matched against name, city, address, description, category
 *   'category'  => category id (int) or slug (string)
 *   'city'      => exact city
 *   'features'  => ['wheelchairEntrance', 'elevator', ...] venue must have ALL available
 *   'parent'    => parent venue id (e.g. all buildings on Ashesi University Campus)
 *   'exclude_parents' => true to leave out venues that have child venues (e.g. the campus itself)
 *   'min_score' => minimum accessibility score (0–100)
 *   'featured'  => true to return featured venues only
 *   'sort'      => 'score' (default) | 'rating' | 'name' | 'newest' | 'reviews'
 *   'limit'     => max rows
 */
function getAllVenues(array $filters = []): array
{
    $where  = ['v.is_active = 1'];
    $params = [];

    if (!empty($filters['search'])) {
        $where[] = '(v.name LIKE :s1 OR v.city LIKE :s2 OR v.address LIKE :s3 OR v.description LIKE :s4 OR c.name LIKE :s5)';
        $term = '%' . trim($filters['search']) . '%';
        foreach (['s1', 's2', 's3', 's4', 's5'] as $k) {
            $params[$k] = $term;
        }
    }

    if (!empty($filters['category'])) {
        if (ctype_digit((string) $filters['category'])) {
            $where[] = 'v.category_id = :category';
            $params['category'] = (int) $filters['category'];
        } else {
            $where[] = 'c.slug = :category';
            $params['category'] = (string) $filters['category'];
        }
    }

    if (!empty($filters['parent'])) {
        $where[] = 'v.parent_id = :parent';
        $params['parent'] = (int) $filters['parent'];
    }

    if (!empty($filters['exclude_parents'])) {
        $where[] = 'NOT EXISTS (SELECT 1 FROM venues ch WHERE ch.parent_id = v.id AND ch.is_active = 1)';
    }

    if (!empty($filters['city'])) {
        $where[] = 'v.city = :city';
        $params['city'] = $filters['city'];
    }

    if (!empty($filters['features']) && is_array($filters['features'])) {
        foreach (array_values($filters['features']) as $i => $key) {
            $where[] = "EXISTS (SELECT 1 FROM venue_features vfx{$i}
                                  JOIN features fx{$i} ON fx{$i}.id = vfx{$i}.feature_id
                                 WHERE vfx{$i}.venue_id = v.id
                                   AND fx{$i}.feature_key = :feat{$i}
                                   AND vfx{$i}.status = 'available')";
            $params["feat{$i}"] = (string) $key;
        }
    }

    if (!empty($filters['featured'])) {
        $where[] = 'v.is_featured = 1';
    }

    $having = '';
    if (isset($filters['min_score']) && $filters['min_score'] !== '') {
        $having = 'HAVING accessibility_score >= :min_score';
        $params['min_score'] = (int) $filters['min_score'];
    }

    $orderBy = [
        'score'   => 'accessibility_score DESC, v.name ASC',
        'rating'  => 'avg_rating DESC, review_count DESC, v.name ASC',
        'name'    => 'v.name ASC',
        'newest'  => 'v.created_at DESC',
        'reviews' => 'review_count DESC, v.name ASC',
    ][$filters['sort'] ?? 'score'] ?? 'accessibility_score DESC, v.name ASC';

    $sql = 'SELECT v.id, v.name, v.slug, v.parent_id, p.name AS parent_name,
                   v.category_id, c.name AS category_name, c.slug AS category_slug,
                   v.description, v.address, v.city, v.postcode, v.latitude, v.longitude,
                   v.image, v.is_featured, v.created_at,
                   ' . scoreSqlExpression() . ' AS accessibility_score,
                   (SELECT ROUND(AVG(r.rating), 1) FROM reviews r
                     WHERE r.venue_id = v.id AND r.status = \'approved\') AS avg_rating,
                   (SELECT COUNT(*) FROM reviews r
                     WHERE r.venue_id = v.id AND r.status = \'approved\') AS review_count
              FROM venues v
              LEFT JOIN categories c ON c.id = v.category_id
              LEFT JOIN venues p ON p.id = v.parent_id
             WHERE ' . implode(' AND ', $where) . '
             ' . $having . '
          ORDER BY ' . $orderBy;

    if (!empty($filters['limit'])) {
        $sql .= ' LIMIT ' . max(1, (int) $filters['limit']);
    }

    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    $venues = $stmt->fetchAll();

    foreach ($venues as &$venue) {
        $venue['accessibility_score'] = (int) $venue['accessibility_score'];
        $venue['avg_rating']          = $venue['avg_rating'] !== null ? (float) $venue['avg_rating'] : null;
        $venue['review_count']        = (int) $venue['review_count'];
        $venue['features_available']  = [];
    }
    unset($venue);

    // Attach the list of available feature keys to each venue (one query),
    // so the explore page's JavaScript filters can work on the same data.
    if ($venues) {
        $ids = array_column($venues, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = getDB()->prepare(
            "SELECT vf.venue_id, f.feature_key FROM venue_features vf
               JOIN features f ON f.id = vf.feature_id
              WHERE vf.status = 'available' AND vf.venue_id IN ($placeholders)"
        );
        $stmt->execute($ids);
        $byVenue = [];
        foreach ($stmt->fetchAll() as $row) {
            $byVenue[$row['venue_id']][] = $row['feature_key'];
        }
        foreach ($venues as &$venue) {
            $venue['features_available'] = $byVenue[$venue['id']] ?? [];
        }
        unset($venue);
    }

    return $venues;
}

/**
 * Venues in exactly the shape js/mock-venues.js uses, so the Explore page's
 * search.js and filters.js work unchanged with real data. In explore.php replace
 *   <script src="js/mock-venues.js"></script>
 * with
 *   <script>const venues = <?= json_encode(getVenuesForExplore()) ?>;</script>
 *
 * Each item: id, name, location, parentVenue, category, score, rating,
 *            reviewCount, image, features { wheelchairEntrance: true, ... }
 */
function getVenuesForExplore(array $filters = []): array
{
    $featureKeys = array_column(getFeatures(), 'feature_key');
    $list = [];
    $filters += ['exclude_parents' => true]; // the mock list shows buildings, not the campus itself
    foreach (getAllVenues($filters) as $v) {
        $features = [];
        foreach ($featureKeys as $key) {
            $features[$key] = in_array($key, $v['features_available'], true);
        }
        $list[] = [
            'id'          => (int) $v['id'],
            'name'        => $v['name'],
            'location'    => $v['address'] === $v['city'] ? $v['city'] : $v['address'] . ', ' . $v['city'],
            'parentVenue' => $v['parent_name'] ?? $v['city'],
            'category'    => $v['category_name'] ?? '',
            'score'       => $v['accessibility_score'],
            'rating'      => $v['avg_rating'] ?? 0,
            'reviewCount' => $v['review_count'],
            'image'       => $v['image'],
            'features'    => $features,
        ];
    }
    return $list;
}

/**
 * Fetch full details of one venue, including category, score, rating summary
 * and every feature with its status.
 *
 * Returns null if the venue does not exist or is inactive (pass $includeInactive
 * = true from admin pages). Extra keys:
 *   'category_name', 'parent_name', 'accessibility_score', 'avg_rating', 'review_count',
 *   'features' => [ 'wheelchairEntrance' => ['id','key','name','description',
 *                    'weight','icon','status','notes','points'], ... ]
 */
function getVenueById($id, bool $includeInactive = false): ?array
{
    $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        return null;
    }

    $sql = 'SELECT v.*, c.name AS category_name, c.slug AS category_slug, p.name AS parent_name
              FROM venues v
              LEFT JOIN categories c ON c.id = v.category_id
              LEFT JOIN venues p ON p.id = v.parent_id
             WHERE v.id = ?' . ($includeInactive ? '' : ' AND v.is_active = 1');
    $stmt = getDB()->prepare($sql);
    $stmt->execute([$id]);
    $venue = $stmt->fetch();
    if (!$venue) {
        return null;
    }

    $venue['features']            = getVenueFeatures($id);
    $venue['accessibility_score'] = array_sum(array_column($venue['features'], 'points'));

    $summary = getVenueRatingSummary($id);
    $venue['avg_rating']   = $summary['average'];
    $venue['review_count'] = $summary['count'];

    return $venue;
}

/**
 * Every scored feature for a venue, keyed by feature_key, in display order.
 * Features with no saved status are returned as 'unknown'.
 */
function getVenueFeatures(int $venueId): array
{
    $stmt = getDB()->prepare(
        "SELECT f.id, f.feature_key, f.name, f.description, f.weight, f.icon,
                COALESCE(vf.status, 'unknown') AS status, vf.notes
           FROM features f
           LEFT JOIN venue_features vf ON vf.feature_id = f.id AND vf.venue_id = ?
       ORDER BY f.sort_order, f.id"
    );
    $stmt->execute([$venueId]);

    $features = [];
    foreach ($stmt->fetchAll() as $row) {
        $features[$row['feature_key']] = [
            'id'          => (int) $row['id'],
            'key'         => $row['feature_key'],
            'name'        => $row['name'],
            'description' => $row['description'],
            'weight'      => (int) $row['weight'],
            'icon'        => $row['icon'],
            'status'      => $row['status'],
            'notes'       => $row['notes'],
            'points'      => $row['status'] === 'available' ? (int) $row['weight'] : 0,
        ];
    }
    return $features;
}

/** All categories (for filters, admin forms). */
function getCategories(): array
{
    return getDB()->query('SELECT id, name, slug, icon FROM categories ORDER BY name')->fetchAll();
}

/** All scored features with weights, in display order. */
function getFeatures(): array
{
    return getDB()->query(
        'SELECT id, feature_key, name, description, weight, icon FROM features ORDER BY sort_order, id'
    )->fetchAll();
}

/* =========================================================================
 * 5. ACCESSIBILITY SCORING
 * ========================================================================= */

/**
 * Calculate the dynamic 0–100 accessibility score for a venue.
 * Each feature marked 'available' earns its weight; 'unavailable' and
 * 'unknown' earn 0. Weights live in the features table and total 100.
 */
function calculateAccessibilityScore($id): int
{
    $stmt = getDB()->prepare(
        "SELECT COALESCE(SUM(f.weight), 0)
           FROM venue_features vf
           JOIN features f ON f.id = vf.feature_id
          WHERE vf.venue_id = ? AND vf.status = 'available'"
    );
    $stmt->execute([(int) $id]);
    return (int) $stmt->fetchColumn();
}

/**
 * Score breakdown for the details/compare pages.
 * Returns ['score', 'max', 'label', 'features' => [...], 'counts' => [status => n]]
 */
function getScoreBreakdown($id): array
{
    $features = getVenueFeatures((int) $id);
    $score    = array_sum(array_column($features, 'points'));
    $counts   = ['available' => 0, 'unavailable' => 0, 'unknown' => 0];
    foreach ($features as $f) {
        $counts[$f['status']]++;
    }
    return [
        'score'    => $score,
        'max'      => array_sum(array_column($features, 'weight')),
        'label'    => getScoreLabel($score),
        'features' => $features,
        'counts'   => $counts,
    ];
}

/** Text band for a score, e.g. for badges: Excellent / Good / Fair / Limited / Poor. */
function getScoreLabel(int $score): string
{
    if ($score >= 85) return 'Excellent';
    if ($score >= 70) return 'Good';
    if ($score >= 50) return 'Fair';
    if ($score >= 25) return 'Limited';
    return 'Poor';
}

/**
 * Check a venue against a user's needs.
 * Returns ['matched' => [...keys], 'missing' => [...keys], 'unknown' => [...keys], 'all_met' => bool]
 */
function matchVenueToRequirements(array $venueFeatures, array $requiredKeys): array
{
    $result = ['matched' => [], 'missing' => [], 'unknown' => []];
    foreach ($requiredKeys as $key) {
        $status = $venueFeatures[$key]['status'] ?? 'unknown';
        if ($status === 'available') {
            $result['matched'][] = $key;
        } elseif ($status === 'unavailable') {
            $result['missing'][] = $key;
        } else {
            $result['unknown'][] = $key;
        }
    }
    $result['all_met'] = !$result['missing'] && !$result['unknown'];
    return $result;
}

/* =========================================================================
 * 6. REVIEWS
 * ========================================================================= */

/**
 * Fetch approved reviews for a venue plus rating averages.
 *
 * Returns:
 *   'reviews'      => list of reviews (newest first) with reviewer_name
 *   'average'      => average overall rating (1 decimal) or null
 *   'accessibility_average' => average accessibility rating or null
 *   'count'        => number of approved reviews
 *   'distribution' => [5 => n, 4 => n, 3 => n, 2 => n, 1 => n]
 */
function getVenueReviews($id, ?int $limit = null): array
{
    $sql = "SELECT r.id, r.venue_id, r.user_id, r.rating, r.accessibility_rating, r.title,
                   r.comment, r.visit_date, r.created_at, r.updated_at, u.full_name AS reviewer_name
              FROM reviews r
              JOIN users u ON u.id = r.user_id
             WHERE r.venue_id = ? AND r.status = 'approved'
          ORDER BY r.created_at DESC";
    if ($limit !== null) {
        $sql .= ' LIMIT ' . max(1, $limit);
    }
    $stmt = getDB()->prepare($sql);
    $stmt->execute([(int) $id]);

    return ['reviews' => $stmt->fetchAll()] + getVenueRatingSummary((int) $id);
}

/** Rating averages, count and 1–5 star distribution for a venue (approved reviews only). */
function getVenueRatingSummary(int $venueId): array
{
    $stmt = getDB()->prepare(
        "SELECT COUNT(*) AS total, ROUND(AVG(rating), 1) AS avg_rating,
                ROUND(AVG(accessibility_rating), 1) AS avg_access,
                SUM(rating = 5) AS r5, SUM(rating = 4) AS r4, SUM(rating = 3) AS r3,
                SUM(rating = 2) AS r2, SUM(rating = 1) AS r1
           FROM reviews WHERE venue_id = ? AND status = 'approved'"
    );
    $stmt->execute([$venueId]);
    $row = $stmt->fetch();

    return [
        'average'               => $row['avg_rating'] !== null ? (float) $row['avg_rating'] : null,
        'accessibility_average' => $row['avg_access'] !== null ? (float) $row['avg_access'] : null,
        'count'                 => (int) $row['total'],
        'distribution'          => [
            5 => (int) $row['r5'], 4 => (int) $row['r4'], 3 => (int) $row['r3'],
            2 => (int) $row['r2'], 1 => (int) $row['r1'],
        ],
    ];
}

/** The logged-in user's existing review of a venue (any status), or null. */
function getUserReview(int $userId, int $venueId): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM reviews WHERE user_id = ? AND venue_id = ?');
    $stmt->execute([$userId, $venueId]);
    return $stmt->fetch() ?: null;
}

/** All reviews written by a user, with venue names (for the dashboard). */
function getUserReviews(int $userId): array
{
    $stmt = getDB()->prepare(
        'SELECT r.*, v.name AS venue_name, v.slug AS venue_slug
           FROM reviews r JOIN venues v ON v.id = r.venue_id
          WHERE r.user_id = ?
       ORDER BY r.created_at DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/** Render 1–5 stars as text with an accessible label, e.g. for review lists. */
function renderStars(float $rating, int $max = 5): string
{
    $full = (int) round($rating);
    $html = '<span class="stars" role="img" aria-label="' . e(number_format($rating, 1)) . ' out of ' . $max . ' stars">';
    for ($i = 1; $i <= $max; $i++) {
        $html .= '<span class="star' . ($i <= $full ? ' star-filled' : '') . '" aria-hidden="true">★</span>';
    }
    return $html . '</span>';
}

/* =========================================================================
 * 7. FAVOURITES, PREFERENCES & RECENTLY VIEWED
 * ========================================================================= */

/** Venues a user has saved, newest first, with score and rating. */
function getUserFavourites($userId): array
{
    $stmt = getDB()->prepare(
        'SELECT v.id, v.name, v.slug, v.address, v.city, v.image, c.name AS category_name,
                fav.created_at AS saved_at,
                ' . scoreSqlExpression() . ' AS accessibility_score,
                (SELECT ROUND(AVG(r.rating), 1) FROM reviews r
                  WHERE r.venue_id = v.id AND r.status = \'approved\') AS avg_rating
           FROM favourites fav
           JOIN venues v ON v.id = fav.venue_id AND v.is_active = 1
           LEFT JOIN categories c ON c.id = v.category_id
          WHERE fav.user_id = ?
       ORDER BY fav.created_at DESC'
    );
    $stmt->execute([(int) $userId]);
    return $stmt->fetchAll();
}

/** Save a venue for a user. Returns true if saved (or already saved). */
function addFavourite($userId, $venueId): bool
{
    if (!getVenueById($venueId)) {
        return false;
    }
    $stmt = getDB()->prepare('INSERT IGNORE INTO favourites (user_id, venue_id) VALUES (?, ?)');
    return $stmt->execute([(int) $userId, (int) $venueId]);
}

/** Remove a saved venue. Returns true if a row was removed. */
function removeFavourite($userId, $venueId): bool
{
    $stmt = getDB()->prepare('DELETE FROM favourites WHERE user_id = ? AND venue_id = ?');
    $stmt->execute([(int) $userId, (int) $venueId]);
    return $stmt->rowCount() > 0;
}

function isFavourite($userId, $venueId): bool
{
    $stmt = getDB()->prepare('SELECT 1 FROM favourites WHERE user_id = ? AND venue_id = ?');
    $stmt->execute([(int) $userId, (int) $venueId]);
    return (bool) $stmt->fetchColumn();
}

/** Feature keys a user has said they need, e.g. ['wheelchairEntrance', 'accessibleToilet']. */
function getUserPreferences($userId): array
{
    $stmt = getDB()->prepare(
        'SELECT f.feature_key FROM user_preferences p JOIN features f ON f.id = p.feature_id
          WHERE p.user_id = ? ORDER BY f.sort_order'
    );
    $stmt->execute([(int) $userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/** Replace a user's needed features with the given feature keys. */
function saveUserPreferences($userId, array $featureKeys): void
{
    $db = getDB();
    $db->beginTransaction();
    try {
        $db->prepare('DELETE FROM user_preferences WHERE user_id = ?')->execute([(int) $userId]);
        if ($featureKeys) {
            $insert = $db->prepare(
                'INSERT INTO user_preferences (user_id, feature_id)
                 SELECT ?, id FROM features WHERE feature_key = ?'
            );
            foreach (array_unique($featureKeys) as $key) {
                $insert->execute([(int) $userId, (string) $key]);
            }
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

/** Record that a logged-in user opened a venue (call on venue-details.php). */
function recordRecentlyViewed($userId, $venueId): void
{
    $stmt = getDB()->prepare(
        'INSERT INTO recently_viewed (user_id, venue_id, viewed_at) VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE viewed_at = NOW()'
    );
    $stmt->execute([(int) $userId, (int) $venueId]);
}

/** Most recently viewed venues for a user. */
function getRecentlyViewed($userId, int $limit = 5): array
{
    $stmt = getDB()->prepare(
        'SELECT v.id, v.name, v.city, v.image, rv.viewed_at,
                ' . scoreSqlExpression() . ' AS accessibility_score
           FROM recently_viewed rv
           JOIN venues v ON v.id = rv.venue_id AND v.is_active = 1
          WHERE rv.user_id = ?
       ORDER BY rv.viewed_at DESC
          LIMIT ' . max(1, $limit)
    );
    $stmt->execute([(int) $userId]);
    return $stmt->fetchAll();
}
