/**
 * accessibility.js (Member 3)
 * Display-only enhancement: live "meets my needs" checker.
 * Reads statuses PHP already computed (data-status). It never calculates scores.
 */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('match-form');
    const result = document.getElementById('match-result');
    const list = document.getElementById('feature-list');
    if (!form || !result || !list) return;

    const submitBtn = document.getElementById('match-submit');
    if (submitBtn) submitBtn.hidden = true;   // live update replaces the button (works without JS too)

    const statusByKey = {};
    list.querySelectorAll('li[data-feature]').forEach(li => {
        statusByKey[li.dataset.feature] = { status: li.dataset.status, label: li.dataset.label };
    });

    function line(symbol, text, names) {
        const p = document.createElement('p');
        p.textContent = `${symbol} ${text}: ${names.join(', ')}`;
        return p;
    }

    function update() {
        const picked = [...form.querySelectorAll('input[name="needs[]"]:checked')].map(i => i.value);
        result.replaceChildren();
        if (!picked.length) return;

        const met = [], missing = [], unknown = [];
        picked.forEach(key => {
            const f = statusByKey[key];
            if (!f) return;
            if (f.status === 'available') met.push(f.label);
            else if (f.status === 'unavailable') missing.push(f.label);
            else unknown.push(f.label);
        });

        const head = document.createElement('p');
        const strong = document.createElement('strong');
        strong.textContent = `${met.length} of ${picked.length} needs confirmed.`;
        head.appendChild(strong);
        result.appendChild(head);
        if (met.length) result.appendChild(line('✓', 'Available', met));
        if (missing.length) result.appendChild(line('✗', 'Not available', missing));
        if (unknown.length) result.appendChild(line('?', 'Not yet verified', unknown));
    }

    form.addEventListener('change', update);
    form.addEventListener('submit', e => e.preventDefault());
    update();
});
