<?php
/**
 * SEND A REQUEST TO A VENUE (Member 2)
 * The form a user fills in to ask a venue about accessibility features.
 */
// auth-check.php sends visitors who are not logged in to login.php,
// then brings them back to this page after they sign in.
require_once __DIR__ . '/includes/auth-check.php';

$venueId = (int) ($_GET['venue_id'] ?? 0);
$db      = getDB();

$stmt = $db->prepare('SELECT id, name, contact_email FROM venues WHERE id = :id AND is_active = 1');
$stmt->execute(['id' => $venueId]);
$venue = $stmt->fetch();

if (!$venue) {
    setFlash('error', 'That venue could not be found.');
    redirect('explore.php');
}

// Every feature, for the checkboxes.
$features = $db->query('SELECT feature_key, name FROM features ORDER BY sort_order, name')->fetchAll();

// Features to pre-tick, passed in the link from the venue card.
$preselected = array_filter(explode(',', (string) ($_GET['features'] ?? '')));

$pageTitle  = 'Send a request | AccessAble';
$pageStyles = ['css/explore.css'];

include 'includes/header.php';
include 'includes/navigation.php';
?>

<main id="main-content" class="explore">

    <p><a href="explore.php">&larr; Back to Explore</a></p>
    <h1>Send a request to <?= e($venue['name']) ?></h1>
    <p>Tell this venue which accessibility features you need, and what you would like to know.</p>

    <?php displayFlash(); ?>

    <form class="request-form" method="post" action="actions/feedback-process.php">
        <?= csrfField() ?>
        <input type="hidden" name="venue_id" value="<?= (int) $venue['id'] ?>">

        <fieldset>
            <legend>Which features would you like to ask about?</legend>
            <?php foreach ($features as $f): ?>
                <label>
                    <input type="checkbox" name="features[]"
                           value="<?= e($f['feature_key']) ?>"
                           <?= in_array($f['feature_key'], $preselected, true) ? 'checked' : '' ?>>
                    <?= e($f['name']) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <label for="message">Your message</label>
        <textarea id="message" name="message" rows="6" minlength="10" maxlength="1000" required></textarea>

        <p class="request-note">
            Prototype notice: your request is recorded in AccessAble
            <?= !empty($venue['contact_email']) ? 'and addressed to ' . e($venue['contact_email']) : '' ?>.
            It is not actually emailed.
        </p>

        <button type="submit">Send request</button>
    </form>

</main>

<?php include 'includes/footer.php'; ?>