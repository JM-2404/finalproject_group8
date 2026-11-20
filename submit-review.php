<?php
/**
 * AccessAble — Write or edit a review
 * Owner: Member 4
 * Link here from venue-details.php:  submit-review.php?venue_id=<id>
 * Form posts to actions/review-process.php
 */
require_once __DIR__ . '/includes/auth-check.php';

$venue = getVenueById($_GET['venue_id'] ?? null);
if (!$venue) {
    setFlash('error', 'That venue could not be found.');
    redirect('explore.php');
}

$userId   = currentUserId();
$existing = getUserReview($userId, (int) $venue['id']);
$isEdit   = $existing !== null;
$errors   = getFormErrors();

// Refill from the last failed submission, otherwise from the saved review.
$hasOld = !empty($_SESSION['old_input']);
$value  = function (string $key) use ($existing, $hasOld): string {
    if ($hasOld) {
        return old($key);
    }
    return $existing && isset($existing[$key]) ? (string) $existing[$key] : '';
};

$ratingLabels = [1 => 'Poor', 2 => 'Fair', 3 => 'Good', 4 => 'Very good', 5 => 'Excellent'];
$today        = date('Y-m-d');

/** Accessible star-rating control built from radio buttons. */
function starRadioGroup(string $name, string $legend, string $hint, string $current, array $labels, array $errors): string
{
    $err  = $errors[$name] ?? null;
    $html = '<fieldset class="rating-fieldset' . ($err ? ' has-error' : '') . '"'
          . ($err ? ' aria-describedby="' . $name . '-error"' : '') . '>';
    $html .= '<legend>' . e($legend) . ' <span class="required" aria-hidden="true">*</span></legend>';
    $html .= '<p class="field-hint">' . e($hint) . '</p>';
    $html .= '<div class="star-rating" data-rating-group="' . e($name) . '">';
    for ($i = 5; $i >= 1; $i--) { // reversed so CSS sibling selectors can highlight lower stars
        $id    = $name . '-' . $i;
        $html .= '<input type="radio" id="' . $id . '" name="' . e($name) . '" value="' . $i . '"'
               . ((string) $i === $current ? ' checked' : '') . ' required>';
        $html .= '<label for="' . $id . '" title="' . e($labels[$i]) . '">'
               . '<span aria-hidden="true">★</span>'
               . '<span class="visually-hidden">' . $i . ' star' . ($i > 1 ? 's' : '') . ' – ' . e($labels[$i]) . '</span>'
               . '</label>';
    }
    $html .= '</div>';
    $html .= '<p class="rating-text" aria-live="polite">'
           . ($current !== '' && isset($labels[(int) $current]) ? e($labels[(int) $current]) : 'Select a rating') . '</p>';
    if ($err) {
        $html .= '<p class="field-error" id="' . $name . '-error">' . e($err) . '</p>';
    }
    return $html . '</fieldset>';
}

$pageTitle  = ($isEdit ? 'Edit Your Review' : 'Write a Review') . ' | ' . $venue['name'] . ' | AccessAble';
$pageStyles = ['css/auth.css'];
$showFlashInPage = true; // messages appear inside the form card
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main id="main-content" class="review-page">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
            <li><a href="explore.php">Explore</a></li>
            <li><a href="<?= e(venueUrl((int) $venue['id'])) ?>"><?= e($venue['name']) ?></a></li>
            <li aria-current="page"><?= $isEdit ? 'Edit review' : 'Write a review' ?></li>
        </ol>
    </nav>

    <div class="review-layout">
        <aside class="review-venue-summary" aria-label="Venue summary">
            <?php if (!empty($venue['image'])): ?>
                <img src="<?= e($venue['image']) ?>" alt="" class="review-venue-image">
            <?php endif; ?>
            <h2 class="review-venue-name"><?= e($venue['name']) ?></h2>
            <p class="review-venue-meta">
                <?= e($venue['category_name'] ?? 'Venue') ?> · <?= e($venue['city']) ?>
            </p>
            <div class="review-venue-score">
                <span class="score-value"><?= (int) $venue['accessibility_score'] ?></span>
                <span class="score-max">/ 100</span>
                <span class="score-label"><?= e(getScoreLabel((int) $venue['accessibility_score'])) ?> accessibility</span>
            </div>
            <ul class="review-feature-list">
                <?php foreach ($venue['features'] as $f): ?>
                    <li class="feature-<?= e($f['status']) ?>">
                        <span class="feature-status-icon" aria-hidden="true">
                            <?= $f['status'] === 'available' ? '✓' : ($f['status'] === 'unavailable' ? '✕' : '?') ?>
                        </span>
                        <?= e($f['name']) ?>
                        <span class="visually-hidden">: <?= e($f['status']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <section class="auth-card review-card" aria-labelledby="review-heading">
            <div class="auth-header">
                <h1 id="review-heading"><?= $isEdit ? 'Edit your review' : 'Write a review' ?></h1>
                <p class="auth-subtitle">Tell others how accessible <?= e($venue['name']) ?> was for you.</p>
            </div>

            <?php displayFlash(); ?>

            <?php if ($isEdit && $existing['status'] === 'hidden'): ?>
                <div class="flash flash-warning" role="status">
                    Your review is currently hidden by a moderator. Editing it will send it for review again.
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['general'])): ?>
                <div class="flash flash-error" role="alert"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <form action="actions/review-process.php" method="post" class="auth-form review-form" id="review-form" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="venue_id" value="<?= (int) $venue['id'] ?>">

                <div class="rating-row">
                    <?= starRadioGroup('rating', 'Overall experience', 'Food, service, atmosphere — your visit overall.',
                        $value('rating'), $ratingLabels, $errors) ?>
                    <?= starRadioGroup('accessibility_rating', 'Accessibility', 'How easy was it to get in and around?',
                        $value('accessibility_rating'), $ratingLabels, $errors) ?>
                </div>

                <div class="form-group<?= isset($errors['title']) ? ' has-error' : '' ?>">
                    <label for="title">Review title <span class="required" aria-hidden="true">*</span></label>
                    <input type="text" id="title" name="title" value="<?= e($value('title')) ?>"
                           required minlength="3" maxlength="120" placeholder="e.g. Step-free and welcoming"
                           <?= isset($errors['title']) ? 'aria-invalid="true" aria-describedby="title-error"' : '' ?>>
                    <?php if (isset($errors['title'])): ?>
                        <p class="field-error" id="title-error"><?= e($errors['title']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group<?= isset($errors['comment']) ? ' has-error' : '' ?>">
                    <label for="comment">Your review <span class="required" aria-hidden="true">*</span></label>
                    <textarea id="comment" name="comment" rows="7" required minlength="20" maxlength="2000"
                              placeholder="What worked well? What was difficult? Mention entrances, toilets, lifts, parking, staff help…"
                              aria-describedby="comment-count<?= isset($errors['comment']) ? ' comment-error' : '' ?>"
                              <?= isset($errors['comment']) ? 'aria-invalid="true"' : '' ?>><?= e($value('comment')) ?></textarea>
                    <p class="char-counter" id="comment-count" data-for="comment" data-min="20" data-max="2000" aria-live="polite"></p>
                    <?php if (isset($errors['comment'])): ?>
                        <p class="field-error" id="comment-error"><?= e($errors['comment']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group<?= isset($errors['visit_date']) ? ' has-error' : '' ?>">
                    <label for="visit_date">When did you visit? <span class="optional">(optional)</span></label>
                    <input type="date" id="visit_date" name="visit_date" value="<?= e($value('visit_date')) ?>"
                           max="<?= e($today) ?>"
                           <?= isset($errors['visit_date']) ? 'aria-invalid="true" aria-describedby="visit_date-error"' : '' ?>>
                    <?php if (isset($errors['visit_date'])): ?>
                        <p class="field-error" id="visit_date-error"><?= e($errors['visit_date']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <a href="<?= e(venueUrl((int) $venue['id'])) ?>" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update review' : 'Submit review' ?></button>
                </div>
            </form>

            <?php if ($isEdit): ?>
                <form action="actions/review-process.php" method="post" class="delete-review-form" id="delete-review-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="venue_id" value="<?= (int) $venue['id'] ?>">
                    <button type="submit" class="btn-link btn-danger-link">Delete my review</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</main>

<script src="js/reviews.js" defer></script>
<?php
clearOldInput();
include __DIR__ . '/includes/footer.php';
