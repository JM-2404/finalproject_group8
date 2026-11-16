<?php
/**
 * venue-details.php  (Member 3)
 * URL: venue-details.php?id=1     Optional: &needs[]=elevator&needs[]=ramps
 */
if (!defined('USE_MOCK')) define('USE_MOCK', true);   // set to false once the database is wired in

if (USE_MOCK) {
    require_once __DIR__ . '/includes/functions-member3.php';
    require_once __DIR__ . '/includes/mock-data.php';
} else {
    require_once __DIR__ . '/includes/functions.php';          // Member 4 (session, helpers, DB)
    require_once __DIR__ . '/includes/functions-member3.php';  // scoring + matching
}

// 1. Validate id (integer only)
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$venue = null; $result = null; $reviews = []; $avg = null;
if ($id) {
    if (USE_MOCK) {
        $venue = mockGetVenue($id);
        if ($venue) { $result = mockScore($id); $reviews = mockReviews($id); }
    } else {
        $venue = getVenueById($id);                         // Member 4
        if ($venue) {
            $result = getAccessibilityBreakdown((int)$venue['id']);   // Member 3: single source of the score
            $raw = getVenueReviews((int)$venue['id']);                // Member 4 (list, or ['reviews'=>..,'average'=>..])
            if (isset($raw['reviews'])) { $reviews = $raw['reviews']; $avg = $raw['average'] ?? $raw['avg_rating'] ?? null; }
            else { $reviews = is_array($raw) ? $raw : []; }
        }
    }
}
if (!$venue) http_response_code(404);

// 2. Requirement matching (whitelisted against real feature keys)
$needs = []; $match = null;
if ($venue) {
    $needs = array_values(array_intersect((array)($_GET['needs'] ?? []), array_keys($result['breakdown'])));
    if ($needs) $match = matchRequirements($result['breakdown'], $needs);
    if ($avg === null && $reviews) $avg = round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1);
}

$loggedIn = !USE_MOCK && function_exists('isLoggedIn') && isLoggedIn();
$csrf = (!USE_MOCK && function_exists('csrfField')) ? csrfField() : '';

$pageTitle  = ($venue ? $venue['name'] : 'Venue not found') . ' | AccessAble';
$pageStyles = ['css/venue.css'];
if (is_file(__DIR__ . '/includes/header.php')) {
    include __DIR__ . '/includes/header.php';
    if (is_file(__DIR__ . '/includes/navigation.php')) include __DIR__ . '/includes/navigation.php';
} else {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . e($pageTitle) . '</title><link rel="stylesheet" href="css/venue.css"></head><body>';
}
?>
<main id="main-content" class="venue-page">
<?php if (!$venue): ?>
    <section class="notice">
        <h1>Venue not found</h1>
        <p>We could not find that venue. It may have been removed.</p>
        <p><a class="btn btn-primary" href="explore.php">Browse all venues</a></p>
    </section>
<?php else: $score = $result['score']; ?>
    <nav aria-label="Breadcrumb" class="crumbs">
        <a href="explore.php">Explore</a> <span aria-hidden="true">/</span> <span aria-current="page"><?= e($venue['name']) ?></span>
    </nav>

    <header class="venue-head">
        <div>
            <p class="venue-category"><?= e($venue['category_name'] ?? 'Venue') ?></p>
            <h1><?= e($venue['name']) ?></h1>
            <p class="venue-address"><?= e(trim(($venue['address'] ?? '') . (isset($venue['city']) && $venue['city'] !== '' ? ', ' . $venue['city'] : ''), ', ')) ?></p>
        </div>

        <section class="score-card" aria-labelledby="score-heading">
            <h2 id="score-heading" class="score-card__title">Accessibility score</h2>
            <p class="score-card__value"><strong><?= (int)$score ?></strong><span>/100</span></p>
            <div class="score-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                 aria-valuenow="<?= (int)$score ?>" aria-label="Accessibility score <?= (int)$score ?> out of 100">
                <span class="score-bar__fill" style="width: <?= (int)$score ?>%"></span>
            </div>
            <p class="score-card__band"><?= e(scoreBand($score)) ?></p>
            <p class="score-card__verified"><?= (int)$result['verified'] ?> of <?= (int)$result['total'] ?> features verified. Unknown features score 0.</p>
        </section>
    </header>

    <section class="block">
        <h2>About this venue</h2>
        <p><?= nl2br(e($venue['description'] ?? '')) ?></p>
        <div class="actions">
            <a class="btn btn-primary" href="compare.php?ids=<?= (int)$venue['id'] ?>">Compare with other venues</a>
            <?php if ($loggedIn): ?>
                <form method="post" action="actions/favourite-process.php">
                    <?= $csrf ?>
                    <input type="hidden" name="venue_id" value="<?= (int)$venue['id'] ?>">
                    <button class="btn btn-outline" type="submit">Save to favourites</button>
                </form>
            <?php elseif (!USE_MOCK): ?>
                <a class="btn btn-outline" href="login.php?redirect=<?= urlencode('venue-details.php?id=' . (int)$venue['id']) ?>">Log in to save</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="block" aria-labelledby="features-heading">
        <h2 id="features-heading">Accessibility features</h2>
        <ul class="feature-list" id="feature-list">
            <?php foreach ($result['breakdown'] as $key => $f): $m = statusMeta($f['status']); ?>
                <li class="feature <?= e($m['class']) ?>" data-feature="<?= e($key) ?>" data-label="<?= e($f['label']) ?>" data-status="<?= e($f['status']) ?>">
                    <span class="feature__icon" aria-hidden="true"><?= $m['icon'] ?></span>
                    <span class="feature__name"><?= e($f['label']) ?></span>
                    <span class="feature__status"><?= e($m['text']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="block" aria-labelledby="breakdown-heading">
        <h2 id="breakdown-heading">Score breakdown</h2>
        <div class="table-wrap">
            <table class="data-table">
                <caption class="visually-hidden">Points earned for each accessibility feature</caption>
                <thead><tr><th scope="col">Feature</th><th scope="col">Status</th><th scope="col">Weight</th><th scope="col">Points earned</th></tr></thead>
                <tbody>
                <?php foreach ($result['breakdown'] as $f): $m = statusMeta($f['status']); ?>
                    <tr>
                        <th scope="row"><?= e($f['label']) ?></th>
                        <td><span aria-hidden="true"><?= $m['icon'] ?></span> <?= e($m['text']) ?></td>
                        <td><?= (int)$f['weight'] ?></td>
                        <td><?= (int)$f['earned'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><th scope="row" colspan="3">Total</th><td><?= (int)$score ?> / 100</td></tr></tfoot>
            </table>
        </div>
    </section>

    <section class="block" aria-labelledby="match-heading">
        <h2 id="match-heading">Does this venue meet my needs?</h2>
        <form method="get" action="venue-details.php" id="match-form">
            <input type="hidden" name="id" value="<?= (int)$venue['id'] ?>">
            <fieldset>
                <legend>Select the features you need</legend>
                <div class="needs-grid">
                <?php foreach ($result['breakdown'] as $key => $f): ?>
                    <label class="need">
                        <input type="checkbox" name="needs[]" value="<?= e($key) ?>" <?= in_array($key, $needs, true) ? 'checked' : '' ?>>
                        <?= e($f['label']) ?>
                    </label>
                <?php endforeach; ?>
                </div>
            </fieldset>
            <button class="btn btn-primary" type="submit" id="match-submit">Check this venue</button>
        </form>

        <div id="match-result" class="match-result" aria-live="polite">
        <?php if ($match): $labels = fn(array $keys) => implode(', ', array_map(fn($k) => $result['breakdown'][$k]['label'], $keys)); ?>
            <p><strong><?= count($match['met']) ?> of <?= count($needs) ?> needs confirmed.</strong></p>
            <?php if ($match['met']): ?><p>✓ Available: <?= e($labels($match['met'])) ?></p><?php endif; ?>
            <?php if ($match['missing']): ?><p>✗ Not available: <?= e($labels($match['missing'])) ?></p><?php endif; ?>
            <?php if ($match['unknown']): ?><p>? Not yet verified: <?= e($labels($match['unknown'])) ?></p><?php endif; ?>
        <?php endif; ?>
        </div>
    </section>

    <section class="block" aria-labelledby="reviews-heading">
        <h2 id="reviews-heading">Reviews<?= $avg !== null ? ' (average ' . e($avg) . ' / 5)' : '' ?></h2>
        <?php if (!$reviews): ?>
            <p>No reviews yet. Be the first to share your experience.</p>
        <?php else: foreach ($reviews as $r): ?>
            <article class="review">
                <h3><?= e($r['title'] ?? 'Review') ?> <span class="review__rating"><?= (int)($r['rating'] ?? 0) ?>/5</span></h3>
                <p class="review__by">by <?= e($r['full_name'] ?? $r['username'] ?? $r['name'] ?? 'a visitor') ?></p>
                <p><?= e($r['comment'] ?? '') ?></p>
                <p class="review__date"><?= e($r['created_at'] ?? '') ?></p>
            </article>
        <?php endforeach; endif; ?>
        <p><a class="btn btn-outline" href="submit-review.php?venue_id=<?= (int)$venue['id'] ?>">Write a review</a></p>
    </section>

    <section class="block">
        <details class="report">
            <summary>Report incorrect information</summary>
            <form method="post" action="actions/report-process.php">
                <?= $csrf ?>
                <input type="hidden" name="venue_id" value="<?= (int)$venue['id'] ?>">
                <label for="report-msg">What is wrong?</label>
                <textarea id="report-msg" name="message" rows="3" maxlength="500" required></textarea>
                <button class="btn btn-outline" type="submit">Send report</button>
            </form>
        </details>
    </section>
<?php endif; ?>
</main>
<script src="js/accessibility.js" defer></script>
<?php
if (is_file(__DIR__ . '/includes/footer.php')) include __DIR__ . '/includes/footer.php';
else echo '</body></html>';
