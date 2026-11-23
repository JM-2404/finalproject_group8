<?php
// Load our Explore data functions (this also loads the database connection).
require_once __DIR__ . '/includes/explore-functions.php';

// Read by Janice's header.php: page title and extra CSS file.
$pageTitle  = "Explore Venues | AccessAble";
$pageStyles = ['css/explore.css'];

// The 5 Explore groups: key => [title, short description].
$groups = [
    'universities'  => ['Universities',   'Ashesi University and Academic City University College'],
    'restaurants'   => ['Restaurants',    'Eateries across Ghana'],
    'tourist-sites' => ['Tourist Sites',  'Heritage, nature and culture across Ghana'],
    'airports'      => ['Airports',       'Airports across Ghana'],
    'recreation'    => ['Recreation',     'Sports, leisure and activity centres'],
];

// Level 1: ?group=...   Level 2 (universities only): &parent=<university id>
$group = $_GET['group'] ?? '';
if (!isset($groups[$group])) {
    $group = '';   // SECURITY: ignore unknown groups
}
$parentId = isset($_GET['parent']) ? (int) $_GET['parent'] : 0;

$allVenues = getExploreVenues();

// The universities themselves = university-group venues with no parent.
// Keyed by id so we can check a chosen parent is real.
$universities = [];
foreach ($allVenues as $v) {
    if ($v['group'] === 'universities' && $v['parentId'] === null) {
        $universities[$v['id']] = $v;
    }
}

// SECURITY: a parent id from the URL is only accepted if it is a real university.
if ($group !== 'universities' || !isset($universities[$parentId])) {
    $parentId = 0;
}

// Count venues per group for the category cards.
// The university records themselves are not counted, only the venues inside them.
$counts = array_fill_keys(array_keys($groups), 0);
foreach ($allVenues as $v) {
    if ($v['group'] === 'universities' && $v['parentId'] === null) {
        continue;
    }
    if (isset($counts[$v['group']])) {
        $counts[$v['group']]++;
    }
}

// Count venues inside each university (for the two university cards).
$insideCounts = [];
foreach ($allVenues as $v) {
    if ($v['parentId'] !== null && isset($universities[$v['parentId']])) {
        $insideCounts[$v['parentId']] = ($insideCounts[$v['parentId']] ?? 0) + 1;
    }
}

// Which screen are we on?
//   'groups'       = the 5 category cards
//   'universities' = the 2 university cards
//   'list'         = venues with search, filters and sorting
if ($group === '') {
    $view = 'groups';
} elseif ($group === 'universities' && $parentId === 0) {
    $view = 'universities';
} else {
    $view = 'list';
}

// Venues to send to JavaScript (list view only).
$venuesForJs = [];
if ($view === 'list') {
    foreach ($allVenues as $v) {
        if ($v['group'] !== $group) {
            continue;
        }
        // For universities, keep only venues inside the chosen university.
        if ($group === 'universities' && $v['parentId'] !== $parentId) {
            continue;
        }
        // TEMPORARY: true only if 'available', until the three-state step.
        $v['features'] = array_map(fn($s) => $s === 'available', $v['features']);
        $venuesForJs[] = $v;
    }
}

// Heading, text and back link for the list view.
if ($group === 'universities' && $parentId !== 0) {
    $heading  = $universities[$parentId]['name'];
    $blurb    = 'Venues at ' . $universities[$parentId]['name'];
    $backUrl  = 'explore.php?group=universities';
    $backText = 'All universities';
} elseif ($group !== '') {
    $heading  = $groups[$group][0];
    $blurb    = $groups[$group][1];
    $backUrl  = 'explore.php';
    $backText = 'All categories';
}

include 'includes/header.php';
include 'includes/navigation.php';
?>

<main id="main-content" class="explore">

<?php if ($view === 'groups'): ?>

    <!-- SCREEN 1: the 5 category cards -->
    <h1>Explore Venues</h1>
    <p>
        Choose a category to see venues across Ghana and check their
        accessibility information before visiting.
    </p>

    <div class="category-grid">
        <?php foreach ($groups as $key => [$title, $text]): ?>
            <a class="category-card" href="explore.php?group=<?= urlencode($key) ?>">
                <h2><?= htmlspecialchars($title) ?></h2>
                <p><?= htmlspecialchars($text) ?></p>
                <!-- Universities has 2 cards inside it, so show that instead of a venue count -->
                <span>
                    <?= $key === 'universities'
                        ? count($universities) . ' universities'
                        : (int) $counts[$key] . ' venues' ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

<?php elseif ($view === 'universities'): ?>

    <!-- SCREEN 2: one card per university -->
    <p><a href="explore.php">&larr; All categories</a></p>
    <h1>Universities</h1>
    <p>Choose a university to see the buildings and facilities inside it.</p>

       <div class="uni-grid">
        <?php foreach ($universities as $u): ?>
            <a class="uni-card"
               href="explore.php?group=universities&amp;parent=<?= (int) $u['id'] ?>">
                <!-- Badge: first two letters of the university name (decoration only) -->
                <span class="uni-badge" aria-hidden="true">
                    <?= htmlspecialchars(strtoupper(mb_substr($u['name'], 0, 2))) ?>
                </span>
                <span class="uni-body">
                    <span class="uni-name"><?= htmlspecialchars($u['name']) ?></span>
                    <span class="uni-place"><?= htmlspecialchars($u['location']) ?></span>
                    <span class="uni-count"><?= (int) ($insideCounts[$u['id']] ?? 0) ?> venues to explore</span>
                </span>
                <span class="uni-arrow" aria-hidden="true">&rarr;</span>
            </a>
        <?php endforeach; ?>
    </div>

<?php else: ?>

    <!-- SCREEN 3: venues with search, filters and sorting -->
    <p><a href="<?= $backUrl ?>">&larr; <?= htmlspecialchars($backText) ?></a></p>
    <h1><?= htmlspecialchars($heading) ?></h1>
    <p><?= htmlspecialchars($blurb) ?></p>

    <section class="search-bar">
        <label for="search-input">Search venues</label>
        <input type="text" id="search-input" placeholder="Search by name, location or category">

        <label for="sort-select">Sort by</label>
        <select id="sort-select">
            <option value="score">Accessibility score</option>
            <option value="rating">Rating</option>
            <option value="name">A to Z</option>
        </select>
    </section>

    <div class="explore-layout">
        <!-- Checkbox values must match feature_key values in the database -->
        <aside class="filters">
            <h2>Accessibility filters</h2>
            <label><input type="checkbox" value="wheelchairEntrance"> Wheelchair entrance</label>
            <label><input type="checkbox" value="ramps"> Ramps</label>
            <label><input type="checkbox" value="elevator"> Elevator</label>
            <label><input type="checkbox" value="accessibleToilet"> Accessible toilet</label>
            <label><input type="checkbox" value="parking"> Accessible parking</label>
            <label><input type="checkbox" value="wideDoors"> Wide doors</label>
            <button type="button" id="clear-filters">Clear filters</button>
        </aside>

        <section class="results">
            <p id="result-count" aria-live="polite"></p>
            <div id="venue-list"></div>
        </section>
    </div>

    <script>
        // PHP hands the venues to JavaScript as JSON. The HEX flags stop special
        // characters in venue text from breaking out of the script tag.
        const venues = <?= json_encode($venuesForJs, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    </script>
    <!-- Order matters: filters.js first, because search.js uses its function -->
    <script src="js/filters.js"></script>
    <script src="js/search.js"></script>

<?php endif; ?>

</main>

<?php include 'includes/footer.php'; ?>