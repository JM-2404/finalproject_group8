<?php
/**
 * compare.php  (Member 3)
 * URL: compare.php?ids=1,2,3   (max 3 venues, integers only). &add=4 adds a venue.
 */
if (!defined('USE_MOCK')) define('USE_MOCK', true);   // set to false once the database is wired in
const MAX_COMPARE = 3;

if (USE_MOCK) {
    require_once __DIR__ . '/includes/functions-member3.php';
    require_once __DIR__ . '/includes/mock-data.php';
    $allVenues = mockAllVenues();
} else {
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/functions-member3.php';
    $allVenues = getAllVenues();                      // Member 4: rows with id + name
}

$ids = array_map('intval', explode(',', (string)($_GET['ids'] ?? '')));
if (isset($_GET['add'])) $ids[] = (int)$_GET['add'];
$ids = array_values(array_unique(array_filter($ids, fn($i) => $i > 0)));

$data = [];
foreach ($ids as $id) {
    if (count($data) >= MAX_COMPARE) break;
    $v = USE_MOCK ? mockGetVenue($id) : getVenueById($id);
    if (!$v) continue;
    $data[$id] = ['venue' => $v, 'result' => USE_MOCK ? mockScore($id) : getAccessibilityBreakdown((int)$v['id'])];
}
$ids = array_keys($data);
$topScore = $data ? max(array_map(fn($d) => $d['result']['score'], $data)) : 0;

$featureKeys = [];
if ($data) foreach (reset($data)['result']['breakdown'] as $k => $f) $featureKeys[$k] = $f['label'];

$pageTitle  = 'Compare Venues | AccessAble';
$pageStyles = ['css/venue.css'];
if (is_file(__DIR__ . '/includes/header.php')) {
    include __DIR__ . '/includes/header.php';
    if (is_file(__DIR__ . '/includes/navigation.php')) include __DIR__ . '/includes/navigation.php';
} else {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . e($pageTitle) . '</title><link rel="stylesheet" href="css/venue.css"></head><body>';
}
?>
<main id="main-content" class="venue-page">
    <h1>Compare venues</h1>
    <p>Compare up to <?= MAX_COMPARE ?> venues side by side.</p>

    <?php if (count($ids) < MAX_COMPARE): ?>
    <form method="get" action="compare.php" class="add-form" id="add-form">
        <input type="hidden" name="ids" value="<?= e(implode(',', $ids)) ?>">
        <label for="add-venue">Add a venue</label>
        <select id="add-venue" name="add">
            <option value="">Choose a venue</option>
            <?php foreach ($allVenues as $v): if (in_array((int)$v['id'], $ids, true)) continue; ?>
                <option value="<?= (int)$v['id'] ?>"><?= e($v['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit" id="add-submit">Add to comparison</button>
    </form>
    <?php endif; ?>

    <?php if (count($data) < 2): ?>
        <p class="notice">Choose at least two venues to compare.</p>
    <?php else: ?>
        <label class="diff-toggle"><input type="checkbox" id="only-differences"> Show only features that differ</label>
        <div class="table-wrap">
        <table class="data-table compare-table" id="compare-table">
            <caption class="visually-hidden">Accessibility comparison of selected venues</caption>
            <thead>
                <tr>
                    <td><span class="visually-hidden">Feature</span></td>
                    <?php foreach ($data as $id => $d): ?>
                        <th scope="col">
                            <a href="venue-details.php?id=<?= (int)$id ?>"><?= e($d['venue']['name']) ?></a><br>
                            <button type="button" class="link-btn" data-remove-id="<?= (int)$id ?>"
                                    aria-label="Remove <?= e($d['venue']['name']) ?> from comparison">Remove</button>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr><th scope="row">Category</th>
                    <?php foreach ($data as $d): ?><td><?= e($d['venue']['category_name'] ?? '') ?></td><?php endforeach; ?></tr>
                <tr><th scope="row">Accessibility score</th>
                    <?php foreach ($data as $d): $s = $d['result']['score']; ?>
                        <td class="<?= $s === $topScore ? 'is-best' : '' ?>"><strong><?= (int)$s ?>/100</strong>
                            <?= $s === $topScore ? '<span class="best-tag">Highest score</span>' : '' ?></td>
                    <?php endforeach; ?></tr>
                <tr><th scope="row">Features verified</th>
                    <?php foreach ($data as $d): ?><td><?= (int)$d['result']['verified'] ?> of <?= (int)$d['result']['total'] ?></td><?php endforeach; ?></tr>
                <?php foreach ($featureKeys as $key => $label):
                    $statuses = array_map(fn($d) => $d['result']['breakdown'][$key]['status'], $data);
                    $same = count(array_unique($statuses)) === 1; ?>
                    <tr data-same="<?= $same ? 'true' : 'false' ?>" class="<?= $same ? '' : 'row-differs' ?>">
                        <th scope="row"><?= e($label) ?></th>
                        <?php foreach ($statuses as $st): $m = statusMeta($st); ?>
                            <td class="<?= e($m['class']) ?>"><span aria-hidden="true"><?= $m['icon'] ?></span> <?= e($m['text']) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</main>
<script src="js/comparison.js" defer></script>
<?php
if (is_file(__DIR__ . '/includes/footer.php')) include __DIR__ . '/includes/footer.php';
else echo '</body></html>';
