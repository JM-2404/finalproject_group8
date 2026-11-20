<?php
/**
 * AccessAble — Log in
 * Owner: Member 4
 * Form posts to actions/login-process.php
 */
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect(landingPage(isAdmin()));
}

$redirectTo = safeRedirectTarget($_GET['redirect'] ?? '', '');
$errors     = getFormErrors();

$pageTitle  = 'Log In | AccessAble';
$pageStyles = ['css/auth.css'];
$showFlashInPage = true; // messages appear inside the form card
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main id="main-content" class="auth-page">
    <section class="auth-card" aria-labelledby="login-heading">
        <div class="auth-header">
            <h1 id="login-heading">Welcome back</h1>
            <p class="auth-subtitle">Log in to save venues, write reviews and see your dashboard.</p>
        </div>

        <?php displayFlash(); ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="flash flash-error" role="alert"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <form action="actions/login-process.php" method="post" class="auth-form" id="login-form" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="redirect" value="<?= e($redirectTo) ?>">

            <div class="form-group<?= isset($errors['email']) ? ' has-error' : '' ?>">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="<?= e(old('email')) ?>"
                       autocomplete="email" required maxlength="190"
                       <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                <?php if (isset($errors['email'])): ?>
                    <p class="field-error" id="email-error"><?= e($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group<?= isset($errors['password']) ? ' has-error' : '' ?>">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" autocomplete="current-password" required
                           <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>>
                    <button type="button" class="toggle-password" aria-controls="password" aria-pressed="false">Show</button>
                </div>
                <?php if (isset($errors['password'])): ?>
                    <p class="field-error" id="password-error"><?= e($errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Log in</button>
        </form>

        <p class="auth-switch">
            New to AccessAble? <a href="register.php<?= $redirectTo ? '?redirect=' . urlencode($redirectTo) : '' ?>">Create a free account</a>
        </p>
    </section>
</main>

<script src="js/reviews.js" defer></script>
<?php
clearOldInput();
include __DIR__ . '/includes/footer.php';
