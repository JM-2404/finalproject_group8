<?php
/**
 * AccessAble — Create an account
 * Owner: Member 4
 * Form posts to actions/register-process.php
 */
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect(landingPage(isAdmin()));
}

$redirectTo = safeRedirectTarget($_GET['redirect'] ?? '', '');
$errors     = getFormErrors();
$features   = getFeatures();
$oldNeeds   = $_SESSION['old_input']['needs'] ?? [];
if (!is_array($oldNeeds)) {
    $oldNeeds = [];
}

/** Print aria attributes and error message for a field. */
function fieldError(array $errors, string $field): array
{
    if (!isset($errors[$field])) {
        return ['', ''];
    }
    return [
        ' aria-invalid="true" aria-describedby="' . $field . '-error"',
        '<p class="field-error" id="' . $field . '-error">' . e($errors[$field]) . '</p>',
    ];
}

$pageTitle  = 'Create an Account | AccessAble';
$pageStyles = ['css/auth.css'];
$showFlashInPage = true; // messages appear inside the form card
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main id="main-content" class="auth-page">
    <section class="auth-card auth-card-wide" aria-labelledby="register-heading">
        <div class="auth-header">
            <h1 id="register-heading">Create your account</h1>
            <p class="auth-subtitle">Share your experiences and help others find places that work for them.</p>
        </div>

        <?php displayFlash(); ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="flash flash-error" role="alert"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <form action="actions/register-process.php" method="post" class="auth-form" id="register-form" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="redirect" value="<?= e($redirectTo) ?>">

            <?php [$aria, $msg] = fieldError($errors, 'full_name'); ?>
            <div class="form-group<?= $msg ? ' has-error' : '' ?>">
                <label for="full_name">Full name</label>
                <input type="text" id="full_name" name="full_name" value="<?= e(old('full_name')) ?>"
                       autocomplete="name" required minlength="2" maxlength="100"<?= $aria ?>>
                <?= $msg ?>
            </div>

            <?php [$aria, $msg] = fieldError($errors, 'email'); ?>
            <div class="form-group<?= $msg ? ' has-error' : '' ?>">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="<?= e(old('email')) ?>"
                       autocomplete="email" required maxlength="190"<?= $aria ?>>
                <?= $msg ?>
            </div>

            <div class="form-row">
                <?php [$aria, $msg] = fieldError($errors, 'password'); ?>
                <div class="form-group<?= $msg ? ' has-error' : '' ?>">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" autocomplete="new-password"
                               required minlength="8" maxlength="72"
                               aria-describedby="password-hint<?= $msg ? ' password-error' : '' ?>"<?= $msg ? ' aria-invalid="true"' : '' ?>>
                        <button type="button" class="toggle-password" aria-controls="password" aria-pressed="false">Show</button>
                    </div>
                    <p class="field-hint" id="password-hint">At least 8 characters, with a letter and a number.</p>
                    <div class="password-strength" aria-live="polite">
                        <span class="strength-bar"><span class="strength-fill"></span></span>
                        <span class="strength-text"></span>
                    </div>
                    <?= $msg ?>
                </div>

                <?php [$aria, $msg] = fieldError($errors, 'confirm_password'); ?>
                <div class="form-group<?= $msg ? ' has-error' : '' ?>">
                    <label for="confirm_password">Confirm password</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           autocomplete="new-password" required maxlength="72"<?= $aria ?>>
                    <?= $msg ?>
                </div>
            </div>

            <fieldset class="needs-fieldset">
                <legend>Your accessibility needs <span class="optional">(optional)</span></legend>
                <p class="field-hint">We'll highlight whether each venue meets these. You can change them later on your dashboard.</p>
                <div class="needs-grid">
                    <?php foreach ($features as $feature): ?>
                        <label class="need-option">
                            <input type="checkbox" name="needs[]" value="<?= e($feature['feature_key']) ?>"
                                <?= in_array($feature['feature_key'], $oldNeeds, true) ? 'checked' : '' ?>>
                            <span><?= e($feature['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <?php [$aria, $msg] = fieldError($errors, 'terms'); ?>
            <div class="form-group form-check<?= $msg ? ' has-error' : '' ?>">
                <label class="check-label">
                    <input type="checkbox" id="terms" name="terms" value="1" required
                        <?= old('terms') === '1' ? 'checked' : '' ?><?= $aria ?>>
                    <span>I agree to write honest reviews based on my own visits.</span>
                </label>
                <?= $msg ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create account</button>
        </form>

        <p class="auth-switch">
            Already have an account? <a href="login.php<?= $redirectTo ? '?redirect=' . urlencode($redirectTo) : '' ?>">Log in</a>
        </p>
    </section>
</main>

<script src="js/reviews.js" defer></script>
<?php
clearOldInput();
include __DIR__ . '/includes/footer.php';
