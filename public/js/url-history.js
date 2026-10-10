(() => {
    const key = 'og-url-history';
    const preference = 'og-url-history-enabled';
    const toggle = document.querySelector('#history-enabled');
    const list = document.querySelector('#history-list');
    const suggestions = document.querySelector('#recent-url-suggestions');
    const clear = document.querySelector('#clear-url-history');
    const status = document.querySelector('#history-status');
    const input = document.querySelector('#url');
    const form = document.querySelector('#form');
    const translate = typeof t === 'function' ? t : text => text;
    let entries = [];
    let available = true;
    function valid(value) {
        if (typeof value !== 'string' || value.length > 2048) return false;
        try { const url = new URL(value); return ['http:', 'https:'].includes(url.protocol) && !url.username && !url.password; } catch { return false; }
    }
    function read() {
        try {
            toggle.checked = localStorage.getItem(preference) !== 'false';
            const stored = JSON.parse(localStorage.getItem(key) || '[]');
            entries = toggle.checked && Array.isArray(stored) ? [...new Set(stored.filter(valid))].slice(0,20) : [];
        } catch { entries = []; available = false; }
    }
    function save() {
        try {
            if (toggle.checked && entries.length) localStorage.setItem(key, JSON.stringify(entries));
            else localStorage.removeItem(key);
            localStorage.setItem(preference, String(toggle.checked));
        } catch { available = false; }
    }
    function render() {
        list.replaceChildren();
        suggestions.replaceChildren();
        for (const url of entries) {
            const row = document.createElement('li');
            const check = document.createElement('button');
            check.type = 'button'; check.className = 'history-url'; check.textContent = url;
            check.title = translate('Erneut prüfen') + ': ' + url;
            check.setAttribute('aria-label', translate('Erneut prüfen') + ': ' + url);
            check.addEventListener('click', () => {
                if (document.querySelector('#submit').disabled) return;
                input.value = url;
                input.dispatchEvent(new Event('input', {bubbles:true}));
                form.requestSubmit();
            });
            const remove = document.createElement('button');
            remove.type = 'button'; remove.className = 'history-remove'; remove.textContent = '×';
            remove.setAttribute('aria-label', translate('Eintrag löschen') + ': ' + url);
            remove.addEventListener('click', () => { entries = entries.filter(value => value !== url); save(); render(); });
            row.append(check, remove); list.append(row);
            const option = document.createElement('option'); option.value = url; suggestions.append(option);
        }
        clear.disabled = !entries.length;
        status.textContent = !available ? translate('Der Browserspeicher ist nicht verfügbar. Der Verlauf bleibt nur bis zum Neuladen erhalten.') : !toggle.checked ? translate('Der URL-Verlauf ist deaktiviert.') : !entries.length ? translate('Noch keine gespeicherten URLs.') : '';
    }
    document.addEventListener('og:check-started', event => {
        const url = event.detail?.url;
        if (!toggle.checked || !valid(url)) return;
        entries = [url, ...entries.filter(value => value !== url)].slice(0,20);
        save(); render();
    });
    toggle.addEventListener('change', () => { if (!toggle.checked) entries = []; save(); render(); });
    clear.addEventListener('click', () => { entries = []; save(); render(); });
    window.addEventListener('storage', event => { if ([key, preference, null].includes(event.key)) { read(); render(); } });
    read(); render();
})();
