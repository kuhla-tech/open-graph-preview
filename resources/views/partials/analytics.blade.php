<div id="analytics-consent" class="consent-panel" role="region" aria-label="{{ __('Analyse-Einstellungen') }}" hidden>
<p><strong>{{ __('Besucherstatistik mit Matomo') }}</strong></p>
@if(config('monitoring.matomo_enabled'))<p>{{ __('Mit deiner Zustimmung analysiert der Betreiber dieser Instanz Besuche mit Matomo. Prüf-URLs werden nicht erfasst. Du kannst deine Auswahl jederzeit ändern.') }}</p>@endif
@unless(config('monitoring.matomo_enabled'))
<p>{{ __('Die Besucheranalyse ist in dieser Installation deaktiviert. Es werden keine Besuche an Matomo gesendet.') }}</p>
@endunless
@if(config('monitoring.enabled'))
<p>{{ __('Für technische Fehler, bereinigte Logs und Anwendungsmetriken verwenden wir Sentry. Eingegebene Prüf-URLs werden nicht übermittelt.') }}</p>
@endif
<div class="consent-actions">@if(config('monitoring.matomo_enabled'))<button type="button" data-analytics-choice="no">{{ __('Ablehnen') }}</button><button type="button" data-analytics-choice="yes">{{ __('Zustimmen') }}</button>@else<button type="button" data-analytics-choice="no">{{ __('Verstanden') }}</button>@endif<a href="{{ \App\Support\LocalizedUrl::to('/datenschutz') }}">{{ __('Datenschutz') }}</a></div>
</div>
<script>
(() => {
    const panel = document.querySelector('#analytics-consent');
    const enabled = {{ Illuminate\Support\Js::from((bool) config('monitoring.matomo_enabled')) }};
    let active = false;
    let stored = null;
    try { stored = JSON.parse(localStorage.getItem('og-analytics-consent')); } catch {}
    if (!stored || typeof stored.accepted !== 'boolean' || stored.enabled !== enabled || !Number.isFinite(stored.at) || Date.now() - stored.at > 180 * 86400000) stored = null;
    function activate() {
        if (!enabled) return;
        if (active) { window._paq.push(['setConsentGiven']); return; }
        active = true;
        const base = {{ Illuminate\Support\Js::from(rtrim(config('monitoring.matomo_url'), '/').'/') }};
        const queue = window._paq = window._paq || [];
        queue.push(['requireConsent'], ['disableCookies'], ['disableBrowserFeatureDetection'], ['setDoNotTrack', true], ['setReferrerUrl', '']);
        const safePath = /^\/(?:de|en)\/(?:impressum|datenschutz)?\/?$/.test(location.pathname) ? location.pathname : '/';
        queue.push(['setCustomUrl', location.origin + safePath], ['setDocumentTitle', 'Open Graph Studio' + safePath], ['setTrackerUrl', base + 'matomo.php'], ['setSiteId', {{ (int) config('monitoring.matomo_site_id') }}], ['setConsentGiven'], ['trackPageView']);
        const script = document.createElement('script'); script.async = true; script.src = base + 'matomo.js'; document.head.append(script);
    }
    if (stored?.accepted) activate();
    if (!stored) panel.hidden = false;
    document.querySelectorAll('[data-analytics-choice]').forEach(button => button.addEventListener('click', () => {
        const accepted = button.dataset.analyticsChoice === 'yes';
        try { localStorage.setItem('og-analytics-consent', JSON.stringify({accepted, enabled, at:Date.now()})); } catch {}
        if (accepted) activate();
        else if (active) window._paq.push(['forgetConsentGiven']);
        panel.hidden = true;
    }));
    document.querySelectorAll('[data-analytics-settings]').forEach(button => button.addEventListener('click', () => { panel.hidden = false; }));
})();
</script>