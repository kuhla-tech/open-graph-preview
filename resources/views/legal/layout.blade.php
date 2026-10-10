<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
@include('partials.search-verification')
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
@php($canonicalPath = str_replace('{locale}', '', request()->route()->uri()))
<link rel="canonical" href="{{ \App\Support\LocalizedUrl::to($canonicalPath) }}">
@foreach(['de', 'en'] as $language)
<link rel="alternate" hreflang="{{ $language }}" href="{{ \App\Support\LocalizedUrl::to($canonicalPath, $language) }}">
@endforeach
<title>{{ $pageTitle }} · {{ config('app.name') }}</title>
@include('partials.app-icons')
<script>try{document.documentElement.dataset.theme=localStorage.getItem('og-theme')||(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light')}catch{}</script>
<style>
:root{font-family:Inter,Segoe UI,sans-serif;color:#17332f;background:#f5f7f4;color-scheme:light}*{box-sizing:border-box}body{margin:0}header{background:#fff;border-bottom:1px solid #dce4dd}.header-inner,main{width:100%;max-width:1380px;margin:auto;padding:24px 5%}.header-inner{display:flex;align-items:center;justify-content:space-between;gap:24px}.brand{font-size:19px;font-weight:750}.logo{background:#164f40;color:#fff;border-radius:10px;padding:8px 10px;margin-right:10px}.header-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.badge{font-size:12px;padding:7px 12px;border:1px solid #dae4dd;border-radius:30px}.theme-toggle{font:inherit;font-size:13px;padding:10px 14px;border:1px solid #cad6ce;background:transparent;color:inherit;border-radius:12px;cursor:pointer}.language-switch{display:flex;gap:3px;border:1px solid #cad6ce;border-radius:8px;padding:3px}.language-switch a{padding:7px 9px;font-size:12px;font-weight:700;color:inherit;text-decoration:none;border-radius:5px}.language-switch a[aria-current]{background:#164f40;color:white}main{padding-top:48px;padding-bottom:48px}.legal-text{white-space:pre-wrap;overflow-wrap:anywhere}article{max-width:860px;line-height:1.75}h1{font-size:36px;letter-spacing:-1px;line-height:1.2}h2{font-size:21px;margin-top:32px}a{color:#237454}footer{display:flex;gap:22px;flex-wrap:wrap;margin-top:40px;font-size:13px}button{font:inherit;background:#164f40;color:#fff;border:0;border-radius:8px;cursor:pointer}button:focus-visible,a:focus-visible{outline:3px solid #5baf83;outline-offset:3px}:root[data-theme="dark"]{background:#101a17;color:#e0eee6;color-scheme:dark}:root[data-theme="dark"] header{background:#192620;border-color:#33483d}:root[data-theme="dark"] a{color:#88c99e}:root[data-theme="dark"] .badge,:root[data-theme="dark"] .theme-toggle,:root[data-theme="dark"] .language-switch{border-color:#476152}@media(max-width:800px){.badge{display:none}}@media(max-width:600px){.header-inner{flex-wrap:wrap;gap:16px}.brand{font-size:17px}.header-actions{margin-left:auto}h1{font-size:30px}}
@include('partials.consent-css')
@include('partials.scrollbar-css')
</style>
</head>
<body>
@include('partials.header')
<main>
<a href="{{ \App\Support\LocalizedUrl::to('/') }}">← {{ __('Zur Startseite') }}</a>
<article>@yield('content')</article>
<footer><a href="{{ \App\Support\LocalizedUrl::to('/impressum') }}">{{ __('Impressum') }}</a><a href="{{ \App\Support\LocalizedUrl::to('/datenschutz') }}">{{ __('Datenschutz') }}</a><button class="footer-button" data-analytics-settings>{{ __('Analyse-Einstellungen') }}</button>@include('partials.footer-credit')</footer>
</main>
<script>
const toggle=document.querySelector('#theme-toggle');
function update(){const dark=document.documentElement.dataset.theme==='dark';toggle.textContent=dark?{{ Illuminate\Support\Js::from(__('☀ Light-Mode')) }}:{{ Illuminate\Support\Js::from(__('☾ Dark-Mode')) }};toggle.setAttribute('aria-pressed',String(dark));toggle.setAttribute('aria-label',dark?{{ Illuminate\Support\Js::from(__('Zum Light-Mode wechseln')) }}:{{ Illuminate\Support\Js::from(__('Zum Dark-Mode wechseln')) }})}
update();toggle.addEventListener('click',()=>{const theme=document.documentElement.dataset.theme==='dark'?'light':'dark';document.documentElement.dataset.theme=theme;try{localStorage.setItem('og-theme',theme)}catch{}update()});
</script>
@include('partials.analytics')
</body>
</html>

