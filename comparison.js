/**
 * comparison.js (Member 3)
 * - Remove a venue from the comparison
 * - Auto-submit the "add venue" dropdown
 * - Toggle "only differences"
 * Without JS the page still works through the form and plain links.
 */
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const currentIds = (params.get('ids') || '').split(',').map(s => parseInt(s, 10)).filter(n => n > 0);

    document.querySelectorAll('[data-remove-id]').forEach(btn => {
        btn.addEventListener('click', () => {
            const removeId = parseInt(btn.dataset.removeId, 10);
            const remaining = currentIds.filter(id => id !== removeId);
            window.location.href = 'compare.php' + (remaining.length ? '?ids=' + remaining.join(',') : '');
        });
    });

    const addSelect = document.getElementById('add-venue');
    const addForm = document.getElementById('add-form');
    const addBtn = document.getElementById('add-submit');
    if (addSelect && addForm) {
        if (addBtn) addBtn.hidden = true;
        addSelect.addEventListener('change', () => { if (addSelect.value) addForm.submit(); });
    }

    const toggle = document.getElementById('only-differences');
    const table = document.getElementById('compare-table');
    if (toggle && table) {
        toggle.addEventListener('change', () => {
            table.querySelectorAll('tr[data-same="true"]').forEach(row => { row.hidden = toggle.checked; });
        });
    }
});
