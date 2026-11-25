<?php
/**
 * MY REQUESTS (Member 2)
 * Lists every request the logged-in user has sent to venues.
 */
// Sends visitors who are not logged in to login.php, then back here.
require_once __DIR__ . '/includes/auth-check.php';

$db = getDB();

// feature_key => display name, e.g. ramps => "Ramps"
$featureNames = [];
foreach ($db->query('SELECT feature_key, name FROM features') as $row) {
    $featureNames[$row['feature_key']] = $row['name'];
}

// Only THIS user's requests (WHERE user_id), newest first.
$stmt = $db->prepare(
    'SELECT fr.id, fr.requested_features, fr.message, fr.recipient_email,
            fr.status, fr.created_at, v.id AS venue_id, v.name AS venue_name
       FROM feedback_requests fr
       JOIN venues v ON v.id = fr.venue_id
      WHERE fr.user_id = :uid
   ORDER BY fr.created_at DESC'
);
$stmt->execute(['uid' => currentUserId()]);
$requests = $stmt->fetchAll();

// Words for each status (the meaning is never shown by colour alone).
$statusLabels = [
    'recorded' => 'Recorded in AccessAble (prototype, not emailed)',
    'replied'  => 'Replied',
    'closed'   => 'Closed',
];

$pageTitle  = 'My requests | AccessAble';
$pageStyles = ['css/explore.css'];

include 'includes/header.php';
include 'includes/navigation.php';
?>

<main id="main-content" class="explore">

    <h1>My requests</h1>
    <p>Requests you have sent to venues about accessibility features.</p>

    <?php displayFlash(); ?>

    <?php if (!$requests): ?>
        <p>You have not sent any requests yet. <a href="explore.php">Explore venues</a>
           and tick the features you need to find venues you can ask.</p>
    <?php endif; ?>

    <div class="request-list">
        <?php foreach ($requests as $r): ?>
            <?php
                $names = [];
                foreach (explode(',', $r['requested_features']) as $key) {
                    $names[] = $featureNames[$key] ?? $key;
                }
            ?>
            <article class="request-card">
                <h2><?= e($r['venue_name']) ?></h2>
                <p class="request-meta">
                    Sent <?= e(date('j M Y, H:i', strtotime($r['created_at']))) ?>
                    &middot; To: <?= e($r['recipient_email']) ?>
                </p>
                <p><strong>Features asked about:</strong> <?= e(implode(', ', $names)) ?></p>
                <p><strong>Your message:</strong> <?= nl2br(e($r['message'])) ?></p>
                <p class="request-status">
                    Status: <?= e($statusLabels[$r['status']] ?? $r['status']) ?>
                </p>
            </article>
        <?php endforeach; ?>
    </div>

</main>

<?php include 'includes/footer.php'; ?>