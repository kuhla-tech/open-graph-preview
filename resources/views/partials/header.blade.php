<header><div class="header-inner"><a class="brand" id="new-check" href="{{ \App\Support\LocalizedUrl::to() }}" aria-label="{{ __('Neue Prüfung starten') }}" title="{{ __('Neue Prüfung starten') }}">@if(config('branding.logo'))<img src="{{ url(config('branding.logo')) }}" class="brand-image" alt="" width="42" height="42">@else<span class="logo">og</span>@endif{{ config('app.name') }}</a><div class="header-actions"><span class="badge">{{ __('● Vorschau prüfen. Besser teilen.') }}</span><button type="button" id="theme-toggle" class="theme-toggle" aria-pressed="false">{{ __('Dark-Mode') }}</button><nav class="language-switch" aria-label="{{ __('Sprache wählen') }}"><a href="{{ \App\Support\LocalizedUrl::current('de') }}" lang="de" @if(app()->getLocale() === 'de') aria-current="true" @endif>DE</a><a href="{{ \App\Support\LocalizedUrl::current('en') }}" lang="en" @if(app()->getLocale() === 'en') aria-current="true" @endif>EN</a></nav></div></div></header>

<style>.brand-image{width:auto;max-width:110px;height:40px;object-fit:contain;vertical-align:middle;margin-right:10px}header .brand,header .brand:visited{color:inherit;text-decoration:none}header .brand:focus-visible{outline:3px solid #5baf83;outline-offset:7px;border-radius:4px}</style>
<script>
document.querySelector('#new-check').addEventListener('click', event => {
    if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
    const target = new URL(event.currentTarget.href);
    if (target.origin === location.origin && target.pathname === location.pathname) {
        event.preventDefault();
        history.replaceState(null, '', target.href);
        location.reload();
    }
});
document.querySelectorAll('.language-switch a').forEach(link => link.addEventListener('click', () => { link.hash = location.hash; }));
</script>
