(() => {
    const form = document.querySelector('#form');
    const input = document.querySelector('#url');
    const submit = document.querySelector('#submit');
    const tools = document.querySelector('.link-tools');
    const copy = document.querySelector('#copy-check-link');
    const status = document.querySelector('#share-status');
    const fallback = document.querySelector('#share-link-fallback');
    const bookmark = document.querySelector('#preview-bookmarklet');
    const base = new URL(tools.dataset.checkBase);
    const translate = typeof t === 'function' ? t : text => text;
    function validTarget(value) {
        if (!value || value.length > 2048) return false;
        try { return ['http:', 'https:'].includes(new URL(value).protocol); } catch { return false; }
    }
    function shareLink(value) {
        const link = new URL(base);
        link.hash = new URLSearchParams({url: value}).toString();
        return link.href;
    }
    function update() {
        copy.disabled = !validTarget(input.value.trim());
        status.textContent = '';
        fallback.hidden = true;
    }
    input.addEventListener('input', update);
    copy.addEventListener('click', async () => {
        const value = input.value.trim();
        if (!validTarget(value)) return;
        const link = shareLink(value);
        try {
            await navigator.clipboard.writeText(link);
            status.textContent = translate('Prüf-Link kopiert.');
        } catch {
            fallback.value = link;
            fallback.hidden = false;
            fallback.focus();
            fallback.select();
            status.textContent = translate('Bitte kopiere den markierten Link.');
        }
    });
    const bookmarkCode = '(function(){if(!/^https?:$/.test(location.protocol))return;window.open('
        + JSON.stringify(base.href) + '+"#url="+encodeURIComponent(location.href),"_blank","noopener,noreferrer");})()';
    bookmark.href = 'javascript:' + encodeURIComponent(bookmarkCode);
    bookmark.addEventListener('click', event => {
        event.preventDefault();
        status.textContent = translate('Ziehe „Link Preview prüfen“ in deine Lesezeichenleiste. Öffne dann eine Website und klicke auf das Lesezeichen.');
    });
    function start(value) {
        input.value = value;
        update();
        if (!validTarget(value)) {
            status.textContent = translate('Der Prüf-Link muss eine gültige HTTP- oder HTTPS-URL enthalten (maximal 2048 Zeichen).');
            return;
        }
        if (!submit.disabled) form.requestSubmit();
    }
    input.addEventListener('dragover', event => { event.preventDefault(); });
    input.addEventListener('drop', event => {
        event.preventDefault();
        const data = event.dataTransfer;
        const value = (data.getData('text/uri-list') || data.getData('text/plain')).split(/\r?\n/).find(line => line.trim() && !line.startsWith('#'));
        if (value) start(value.trim());
    });
    function readSharedLink() {
        const params = new URLSearchParams(location.hash.slice(1));
        const value = params.get('url');
        if (value !== null) start(value.trim());
    }
    window.addEventListener('hashchange', readSharedLink);
    input.value = '';
    update();
    readSharedLink();
})();
