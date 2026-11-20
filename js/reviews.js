/**
 * AccessAble — Review & authentication form behaviour
 * Owner: Member 4
 * Loaded by: login.php, register.php, submit-review.php
 *
 * All checks here are for convenience only — the PHP handlers in actions/
 * repeat every check on the server, so the forms still work without JS.
 *
 *   1. Star rating labels           (submit-review.php)
 *   2. Character counter            (submit-review.php)
 *   3. Password show/hide           (login.php, register.php)
 *   4. Password strength meter      (register.php)
 *   5. Client-side validation       (all three forms)
 *   6. Delete-review confirmation   (submit-review.php)
 */
(function () {
    'use strict';

    var RATING_LABELS = { 1: 'Poor', 2: 'Fair', 3: 'Good', 4: 'Very good', 5: 'Excellent' };

    /* ------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */

    /** Show an error under a field (or fieldset) and mark it invalid. */
    function showError(field, message) {
        var group = field.closest('.form-group, .rating-fieldset');
        if (!group) return;
        clearError(field);

        var id = (field.id || field.name) + '-client-error';
        var p = document.createElement('p');
        p.className = 'field-error';
        p.id = id;
        p.textContent = message;
        group.appendChild(p);
        group.classList.add('has-error');

        var target = group.classList.contains('rating-fieldset') ? group : field;
        target.setAttribute('aria-invalid', 'true');
        var described = (target.getAttribute('aria-describedby') || '').split(' ').filter(Boolean);
        if (described.indexOf(id) === -1) described.push(id);
        target.setAttribute('aria-describedby', described.join(' '));
    }

    /** Remove any error shown for a field (client or server rendered). */
    function clearError(field) {
        var group = field.closest('.form-group, .rating-fieldset');
        if (!group) return;
        group.querySelectorAll('.field-error').forEach(function (el) { el.remove(); });
        group.classList.remove('has-error');

        var target = group.classList.contains('rating-fieldset') ? group : field;
        target.removeAttribute('aria-invalid');
        var described = (target.getAttribute('aria-describedby') || '')
            .split(' ')
            .filter(function (idRef) { return idRef && !/-error$/.test(idRef); });
        if (described.length) {
            target.setAttribute('aria-describedby', described.join(' '));
        } else {
            target.removeAttribute('aria-describedby');
        }
    }

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value);
    }

    /** Validate a form with the given rules; focus the first problem. */
    function attachValidation(form, rules) {
        if (!form) return;

        form.addEventListener('submit', function (event) {
            var firstInvalid = null;

            rules.forEach(function (rule) {
                var field = form.querySelector(rule.selector);
                if (!field) return;
                var message = rule.check(field, form);
                if (message) {
                    showError(field, message);
                    if (!firstInvalid) firstInvalid = field;
                } else {
                    clearError(field);
                }
            });

            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
                return;
            }

            // Prevent double submissions.
            var button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.dataset.originalText = button.textContent;
                button.textContent = 'Please wait…';
            }
        });

        // Clear a field's error as soon as the user fixes it.
        form.addEventListener('input', function (event) {
            if (event.target.closest('.has-error')) clearError(event.target);
        });
        form.addEventListener('change', function (event) {
            if (event.target.type === 'radio' || event.target.type === 'checkbox') clearError(event.target);
        });
    }

    // Re-enable submit buttons if the page is restored from the back/forward cache.
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('button[data-original-text]').forEach(function (button) {
            button.disabled = false;
            button.textContent = button.dataset.originalText;
        });
    });

    /* ------------------------------------------------------------------
     * 1. Star rating labels
     * ------------------------------------------------------------------ */
    document.querySelectorAll('.star-rating').forEach(function (group) {
        var fieldset = group.closest('.rating-fieldset');
        var output = fieldset ? fieldset.querySelector('.rating-text') : null;
        if (!output) return;

        function describe(value) {
            return value ? value + ' out of 5 – ' + RATING_LABELS[value] : 'Select a rating';
        }

        function selectedValue() {
            var checked = group.querySelector('input:checked');
            return checked ? checked.value : null;
        }

        group.addEventListener('change', function () { output.textContent = describe(selectedValue()); });

        group.querySelectorAll('label').forEach(function (label) {
            var input = document.getElementById(label.htmlFor);
            label.addEventListener('mouseenter', function () { output.textContent = describe(input.value); });
            label.addEventListener('mouseleave', function () { output.textContent = describe(selectedValue()); });
        });

        output.textContent = describe(selectedValue());
    });

    /* ------------------------------------------------------------------
     * 2. Character counter
     * ------------------------------------------------------------------ */
    document.querySelectorAll('.char-counter[data-for]').forEach(function (counter) {
        var field = document.getElementById(counter.dataset.for);
        if (!field) return;
        var min = parseInt(counter.dataset.min, 10) || 0;
        var max = parseInt(counter.dataset.max, 10) || field.maxLength || 0;
        var lastAnnounced = '';

        function update() {
            var length = field.value.trim().length;
            var text;
            counter.classList.remove('is-short', 'is-over');

            if (length < min) {
                text = (min - length) + ' more character' + (min - length === 1 ? '' : 's') + ' needed';
                if (length > 0) counter.classList.add('is-short');
            } else {
                text = length + ' / ' + max + ' characters';
                if (max && length > max) counter.classList.add('is-over');
            }

            // Only update the live region when the message type changes, to avoid
            // a screen reader announcing every keystroke.
            var state = length < min ? 'short' : 'ok';
            if (state !== lastAnnounced) {
                counter.setAttribute('aria-live', 'polite');
                lastAnnounced = state;
            } else {
                counter.setAttribute('aria-live', 'off');
            }
            counter.textContent = text;
        }

        field.addEventListener('input', update);
        update();
    });

    /* ------------------------------------------------------------------
     * 3. Password show / hide
     * ------------------------------------------------------------------ */
    document.querySelectorAll('.toggle-password').forEach(function (button) {
        var input = document.getElementById(button.getAttribute('aria-controls'));
        if (!input) return;
        button.setAttribute('aria-label', 'Show password');

        button.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.textContent = show ? 'Hide' : 'Show';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            input.focus();
        });
    });

    /* ------------------------------------------------------------------
     * 4. Password strength meter
     * ------------------------------------------------------------------ */
    var meter = document.querySelector('.password-strength');
    var newPassword = document.querySelector('#register-form #password');
    if (meter && newPassword) {
        var meterText = meter.querySelector('.strength-text');
        var names = ['', 'Weak', 'Fair', 'Good', 'Strong'];

        newPassword.addEventListener('input', function () {
            var value = newPassword.value;
            var score = 0;
            if (value.length >= 8) score++;
            if (/[A-Za-z]/.test(value) && /\d/.test(value)) score++;
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value) || value.length >= 14) score++;
            if (value.length > 0 && score === 0) score = 1;

            meter.dataset.strength = value ? String(score) : '';
            meterText.textContent = value ? names[score] : '';
        });
    }

    /* ------------------------------------------------------------------
     * 5. Client-side validation
     * ------------------------------------------------------------------ */
    attachValidation(document.getElementById('login-form'), [
        {
            selector: '#email',
            check: function (f) {
                if (!f.value.trim()) return 'Please enter your email address.';
                if (!isValidEmail(f.value.trim())) return 'Please enter a valid email address.';
            }
        },
        {
            selector: '#password',
            check: function (f) { if (!f.value) return 'Please enter your password.'; }
        }
    ]);

    attachValidation(document.getElementById('register-form'), [
        {
            selector: '#full_name',
            check: function (f) {
                var v = f.value.trim();
                if (!v) return 'Please enter your name.';
                if (v.length < 2 || v.length > 100) return 'Name must be between 2 and 100 characters.';
            }
        },
        {
            selector: '#email',
            check: function (f) {
                if (!f.value.trim()) return 'Please enter your email address.';
                if (!isValidEmail(f.value.trim())) return 'Please enter a valid email address.';
            }
        },
        {
            selector: '#password',
            check: function (f) {
                var v = f.value;
                if (!v) return 'Please choose a password.';
                if (v.length < 8) return 'Password must be at least 8 characters.';
                if (v.length > 72) return 'Password must be 72 characters or fewer.';
                if (!/[A-Za-z]/.test(v) || !/\d/.test(v)) return 'Password must include at least one letter and one number.';
            }
        },
        {
            selector: '#confirm_password',
            check: function (f, form) {
                if (f.value !== form.querySelector('#password').value) return 'Passwords do not match.';
            }
        },
        {
            selector: '#terms',
            check: function (f) { if (!f.checked) return 'Please confirm you will write honest reviews.'; }
        }
    ]);

    attachValidation(document.getElementById('review-form'), [
        {
            selector: 'input[name="rating"]',
            check: function (f, form) {
                if (!form.querySelector('input[name="rating"]:checked')) return 'Please choose an overall rating from 1 to 5 stars.';
            }
        },
        {
            selector: 'input[name="accessibility_rating"]',
            check: function (f, form) {
                if (!form.querySelector('input[name="accessibility_rating"]:checked')) return 'Please choose an accessibility rating from 1 to 5 stars.';
            }
        },
        {
            selector: '#title',
            check: function (f) {
                var v = f.value.trim();
                if (!v) return 'Please give your review a title.';
                if (v.length < 3 || v.length > 120) return 'Title must be between 3 and 120 characters.';
            }
        },
        {
            selector: '#comment',
            check: function (f) {
                var v = f.value.trim();
                if (!v) return 'Please write your review.';
                if (v.length < 20) return 'Please write at least 20 characters so your review is helpful to others.';
                if (v.length > 2000) return 'Reviews can be up to 2,000 characters.';
            }
        },
        {
            selector: '#visit_date',
            check: function (f) {
                if (f.value && f.max && f.value > f.max) return 'Visit date cannot be in the future.';
            }
        }
    ]);

    /* ------------------------------------------------------------------
     * 6. Delete-review confirmation
     * ------------------------------------------------------------------ */
    var deleteForm = document.getElementById('delete-review-form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function (event) {
            if (!window.confirm('Delete your review? This cannot be undone.')) {
                event.preventDefault();
            }
        });
    }
})();
