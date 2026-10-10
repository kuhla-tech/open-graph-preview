<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
@include('partials.search-verification')
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="referrer" content="no-referrer">
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    $socialTitle = __(':name – Link-Vorschauen prüfen', ['name' => config('app.name')]);
    $socialDescription = config('site.description_'.app()->getLocale()) ?: __('Prüfe Social-Metadaten, Bilder und Schema.org-Produktdaten. Mit neun Vorschau-Ansichten und Best-Practice-Hinweisen – lokal und online.');
    $socialUrl = \App\Support\LocalizedUrl::to();
    $localizedSocialImage = config('branding.social_image_'.app()->getLocale()) ?: config('branding.social_image');
    $socialImage = $localizedSocialImage ? url($localizedSocialImage) : rtrim(config('app.url'), '/').'/images/'.(app()->getLocale() === 'en' ? 'social-preview-en.png' : 'social-preview-v3.png');
    $socialAlt = __('Open Graph Studio: Link-Vorschauen für Open Graph, Discord, Twitter/X, LinkedIn, Telegram, Bluesky, Mastodon und Slack prüfen.');
@endphp
<title>{{ $socialTitle }}</title>
<meta name="description" content="{{ $socialDescription }}">
<link rel="canonical" href="{{ $socialUrl }}">
<link rel="alternate" hreflang="de" href="{{ \App\Support\LocalizedUrl::to('/', 'de') }}">
<link rel="alternate" hreflang="en" href="{{ \App\Support\LocalizedUrl::to('/', 'en') }}">
<meta property="og:title" content="{{ $socialTitle }}">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ $socialUrl }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:description" content="{{ $socialDescription }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : 'de_DE' }}">
<meta property="og:image" content="{{ $socialImage }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $socialAlt }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $socialTitle }}">
<meta name="twitter:description" content="{{ $socialDescription }}">
<meta name="twitter:image" content="{{ $socialImage }}">
<meta name="twitter:image:alt" content="{{ $socialAlt }}">
@if(config('social.twitter_site'))
<meta name="twitter:site" content="{{ config('social.twitter_site') }}">
@endif
@if(config('social.twitter_creator'))
<meta name="twitter:creator" content="{{ config('social.twitter_creator') }}">
@endif
<script type="application/ld+json">
{!! json_encode([
    '@'.'context' => 'https://schema.org',
    '@'.'type' => 'Product',
    'name' => config('app.name'),
    'description' => $socialDescription,
    'image' => $socialImage,
    'url' => $socialUrl,
    'category' => 'Software',
    'brand' => [
        '@'.'type' => 'Organization',
        'name' => config('branding.owner_name') ?: config('app.name'),
        'url' => config('branding.owner_url') ?: $socialUrl,
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
<meta name="theme-color" content="#164f40">
@include('partials.app-icons')
<link rel="manifest" href="{{ \App\Support\LocalizedUrl::to('/app.webmanifest') }}">
<meta name="application-name" content="{{ config('app.name') }}">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
<link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/fontawesome.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/brands.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/solid.min.css') }}">
<script>window.outboundSource = {{ Illuminate\Support\Js::from(config('site.utm_source') ?: parse_url(config('app.url'), PHP_URL_HOST)) }};</script>
<script src="{{ asset('js/outbound-links.js') }}"></script>
<script>
const translations = {{ Illuminate\Support\Js::from(app()->getLocale() === 'en' ? json_decode(file_get_contents(lang_path('en.json')), true) : []) }};
function t(text) { return translations[text] ?? text; }
</script>
<script>
(() => {
    let theme;
    try { theme = localStorage.getItem('og-theme'); } catch {}
    document.documentElement.dataset.theme = ['dark', 'light'].includes(theme) ? theme : (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
})();
</script>
<style>
:root{font-family:Inter,Segoe UI,sans-serif;color:#17332f;background:#f5f7f4;font-synthesis:none}*{box-sizing:border-box}body{margin:0}header{border-bottom:1px solid #dce4dd;padding:24px 5%;display:flex;align-items:center;justify-content:space-between;background:#fff} .brand{font-size:19px;font-weight:750;letter-spacing:-.5px}.logo{background:#164f40;color:white;border-radius:10px;padding:8px 10px;margin-right:10px}.badge{font-size:12px;padding:7px 12px;border:1px solid #dae4dd;border-radius:30px;color:#466252}main{max-width:1380px;margin:auto;padding:64px 5% 40px}.eyebrow{font-size:12px;font-weight:700;letter-spacing:2px;color:#527f5e}h1{font-size:clamp(34px,4vw,54px);letter-spacing:-2px;line-height:1.1;margin:18px 0} .intro{color:#63766e;line-height:1.7;max-width:660px}form{display:flex;gap:12px;margin:30px 0 12px}input{font:inherit;background:white;border:1px solid #cad6ce;border-radius:12px;padding:22px;flex:1;min-width:0;font-size:18px;outline:none}input:focus{border-color:#237454;box-shadow:0 0 0 3px #23745420}button{font:inherit;font-size:18px;font-weight:650;padding:18px 40px;background:#164f40;color:white;border:0;border-radius:12px;cursor:pointer}button:disabled{opacity:.65;cursor:wait}.hint{font-size:12px;color:#74857a}.layout{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);gap:28px;margin-top:44px}.panel{background:white;border:1px solid #dde5dd;border-radius:18px;padding:26px;min-width:0}.panel-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}h2{font-size:18px;margin:0}.muted{color:#75867d;font-size:12px}.empty{min-height:310px;display:flex;align-items:center;justify-content:center;text-align:center;border:1px dashed #cddad0;border-radius:12px;background:#fafcf9;color:#73877b;padding:30px}.empty strong{display:block;color:#405b4c;margin:18px 0 8px}.card{border:1px solid #dbe3dd;border-radius:12px;overflow:hidden;background:#f6f8f5}.card img{width:100%;aspect-ratio:1.91;object-fit:cover;display:block}.no-image{aspect-ratio:1.91;display:grid;place-items:center;background:#e9efe7;color:#80907e}.card-body{padding:20px}.site{font-size:11px;text-transform:uppercase;letter-spacing:1.4px;color:#657d69}.card h3{margin:10px 0;font-size:22px;overflow-wrap:anywhere}.card p{font-size:14px;line-height:1.6;color:#687b6d;margin-bottom:0;overflow-wrap:anywhere}.footnote{font-size:12px;line-height:1.6;color:#7d8b81;margin-top:16px}.check{display:flex;gap:12px;padding:15px 0;border-bottom:1px solid #edf0eb}.check:last-child{border:0}.icon{width:25px;height:25px;flex-shrink:0;display:grid;place-items:center;border-radius:50%;font-weight:bold}.pass .icon{background:#e5f3e6;color:#2b7346}.warn .icon{background:#fff2d7;color:#986621}.fail .icon{background:#fbe6e3;color:#ac4132}.check b{font-size:13px}.check p{margin:6px 0 0;font-size:12px;line-height:1.5;color:#778479;overflow-wrap:anywhere}.error{background:#fbe6e3;padding:16px;border-radius:10px;color:#9c352b;margin-top:20px}details{margin-top:24px;border:1px solid #dde5dd;border-radius:12px;padding:18px;background:white}summary{cursor:pointer;font-size:13px;font-weight:600}pre{white-space:pre-wrap;overflow-wrap:anywhere;font-size:12px;line-height:1.7}footer{display:flex;justify-content:space-between;color:#809084;font-size:12px;margin-top:36px}footer a{color:inherit}[hidden]{display:none!important}@media(max-width:800px){main{padding-top:36px}.layout{grid-template-columns:1fr}form{flex-direction:column}button{padding:20px}.badge{display:none}.panel{padding:20px}footer{gap:20px}}
:root{color-scheme:light}
:root[data-theme="dark"]{color-scheme:dark;color:#e0eee6;background:#101a17}
.header-actions,.footer-links{display:flex;align-items:center;gap:18px}
.theme-toggle{font-size:13px;padding:10px 14px;border:1px solid #cad6ce;background:transparent;color:inherit;white-space:nowrap}
input{color:inherit}
button:focus-visible,a:focus-visible{outline:3px solid #5baf83;outline-offset:4px}
:root[data-theme="dark"] header,:root[data-theme="dark"] .panel,:root[data-theme="dark"] details{background:#192620;border-color:#33483d}
:root[data-theme="dark"] input{background:#192620;border-color:#476152}
:root[data-theme="dark"] input::placeholder{color:#94a89b}
:root[data-theme="dark"] .theme-toggle,:root[data-theme="dark"] .badge{border-color:#476152;color:#c3d7c9}
:root[data-theme="dark"] .eyebrow{color:#88c99e}
:root[data-theme="dark"] .intro,:root[data-theme="dark"] .hint,:root[data-theme="dark"] .muted,:root[data-theme="dark"] .footnote,:root[data-theme="dark"] footer,:root[data-theme="dark"] .site,:root[data-theme="dark"] .card p,:root[data-theme="dark"] .check p{color:#a8bdae}
:root[data-theme="dark"] .empty{background:#15211b;border-color:#405849;color:#a8bdae}
:root[data-theme="dark"] .empty strong{color:#d0e2d5}
:root[data-theme="dark"] .card{background:#15211b;border-color:#405849}
:root[data-theme="dark"] .no-image{background:#24372b;color:#aec3b1}
:root[data-theme="dark"] .check{border-color:#33483d}
:root[data-theme="dark"] .pass .icon{background:#264932;color:#a6e3b7}
:root[data-theme="dark"] .warn .icon{background:#4b3e21;color:#f1ce88}
:root[data-theme="dark"] .fail .icon,:root[data-theme="dark"] .error{background:#4a2926;color:#ffb7aa}
@media(max-width:600px){header{gap:16px;flex-wrap:wrap}.brand{font-size:17px}.header-actions{margin-left:auto}footer{flex-wrap:wrap}.footer-links{flex-wrap:wrap;gap:14px}}

.discord-chat{background:#f2f3f5;color:#313338;padding:22px;border-radius:12px;display:flex;gap:14px;font-family:Segoe UI,sans-serif}
.discord-avatar{width:38px;height:38px;flex-shrink:0;border-radius:50%;background:#5865f2;color:white;display:grid;place-items:center;font-size:13px;font-weight:700}
.discord-message{min-width:0;flex:1}
.discord-user{font-size:14px;font-weight:650}.discord-time{font-size:11px;font-weight:400;color:#687078;margin-left:8px}
.discord-link{display:block;color:#006ce7;font-size:14px;margin:7px 0 10px;overflow-wrap:anywhere;text-decoration:none}
.discord-link:hover{text-decoration:underline}
.discord-embed{max-width:432px;border-left:4px solid #c4c9ce;border-radius:4px;background:#e3e5e8;padding:16px;overflow:hidden}
.discord-provider{font-size:12px;color:#5c6370;margin-bottom:8px;overflow-wrap:anywhere}
.discord-title{display:block;color:#006ce7;font-size:16px;font-weight:650;text-decoration:none;overflow-wrap:anywhere;line-height:1.4}
.discord-title:hover{text-decoration:underline}
.discord-description{font-size:14px;line-height:1.5;margin:8px 0 0;white-space:pre-wrap;overflow-wrap:anywhere}
.discord-image{display:block;max-width:100%;max-height:300px;width:auto;height:auto;border-radius:4px;margin-top:16px;object-fit:contain}
:root[data-theme="dark"] .discord-chat{background:#313338;color:#dbdee1}
:root[data-theme="dark"] .discord-embed{background:#2b2d31;border-left-color:#1e1f22}
:root[data-theme="dark"] .discord-link,:root[data-theme="dark"] .discord-title{color:#00a8fc}
:root[data-theme="dark"] .discord-time,:root[data-theme="dark"] .discord-provider{color:#b5bac1}
@media(max-width:480px){.discord-chat{padding:14px;gap:10px}.discord-avatar{width:30px;height:30px}.discord-embed{padding:12px}.discord-time{display:block;margin:3px 0}}
.preview-tabs{display:flex;gap:6px;padding:5px;margin-bottom:22px;background:#edf2ec;border-radius:10px}
.preview-tab{flex:1;font-size:14px;padding:12px 16px;background:transparent;color:#53695b;border-radius:7px}
.preview-tab[aria-selected="true"]{background:#fff;color:#164f40;box-shadow:0 1px 4px #17332f18}
:root[data-theme="dark"] .preview-tabs{background:#101a17}
:root[data-theme="dark"] .preview-tab{color:#a8bdae}
:root[data-theme="dark"] .preview-tab[aria-selected="true"]{background:#33483d;color:#e0eee6}
[role="tabpanel"]:focus-visible{outline:3px solid #5baf83;outline-offset:5px;border-radius:12px}
.twitter-post{background:#fff;color:#0f1419;border:1px solid #cfd9de;border-radius:14px;padding:20px}
.twitter-user{font-size:14px;font-weight:700;margin-bottom:14px}.twitter-user span{color:#536471;font-weight:400;margin-left:8px}
.twitter-card{display:block;border:1px solid #cfd9de;border-radius:14px;overflow:hidden;color:inherit;text-decoration:none}
.twitter-card:hover{background:#f7f9f9}
.twitter-card.summary{display:flex;align-items:stretch}
.twitter-image{width:100%;aspect-ratio:2;object-fit:cover;display:block}
.summary .twitter-image{width:120px;min-width:120px;aspect-ratio:1;object-fit:cover;align-self:center}
.twitter-copy{padding:14px;min-width:0}.twitter-domain{font-size:12px;color:#536471;overflow-wrap:anywhere}
.twitter-title{font-size:15px;font-weight:650;line-height:1.4;margin:6px 0;overflow-wrap:anywhere}
.twitter-description{font-size:13px;color:#536471;line-height:1.5;margin:0;overflow-wrap:anywhere}
.twitter-special{padding:26px;background:#eff3f4;color:#536471;text-align:center;font-size:13px}
:root[data-theme="dark"] .twitter-post{background:#000;color:#e7e9ea;border-color:#2f3336}
:root[data-theme="dark"] .twitter-card{border-color:#2f3336}
:root[data-theme="dark"] .twitter-card:hover{background:#16181c}
:root[data-theme="dark"] .twitter-domain,:root[data-theme="dark"] .twitter-description,:root[data-theme="dark"] .twitter-user span{color:#a0a8af}
:root[data-theme="dark"] .twitter-special{background:#16181c;color:#a0a8af}
@media(max-width:480px){.preview-tab{padding:12px 5px;font-size:12px}.twitter-post{padding:12px}.summary .twitter-image{width:90px;min-width:90px}}
.checks-heading{flex-wrap:wrap;gap:14px}
.status-counts{display:flex;flex-wrap:wrap;gap:8px}
.status-count{display:inline-flex;align-items:center;gap:7px;padding:8px 11px;border:1px solid transparent;border-radius:9px;font-size:12px;font-weight:650;white-space:nowrap}
.status-count-icon{font-size:15px;font-weight:800;line-height:1}
.status-count.pass{background:#e5f3e6;color:#24653a;border-color:#bedfc5}
.status-count.warn{background:#fff2d7;color:#815414;border-color:#ecd3a0}
.status-count.fail{background:#fbe6e3;color:#a2382b;border-color:#eebfb7}
:root[data-theme="dark"] .status-count.pass{background:#264932;color:#a6e3b7;border-color:#3c6b4b}
:root[data-theme="dark"] .status-count.warn{background:#4b3e21;color:#f1ce88;border-color:#756030}
:root[data-theme="dark"] .status-count.fail{background:#4a2926;color:#ffb7aa;border-color:#77443e}
.linkedin-post{background:#fff;color:#191919;border:1px solid #ddd;border-radius:10px;overflow:hidden}
.linkedin-author{display:flex;align-items:center;gap:12px;padding:18px;font-size:14px;font-weight:650}
.linkedin-avatar{width:42px;height:42px;border-radius:50%;background:#0a66c2;color:white;display:grid;place-items:center;font-size:12px}
.linkedin-subtitle{display:block;font-size:12px;color:#666;font-weight:400;margin-top:4px}
.linkedin-card{display:block;color:inherit;text-decoration:none;background:#eef3f8}
.linkedin-card img{display:block;width:100%;aspect-ratio:1.91;object-fit:cover}
.linkedin-copy{padding:16px}.linkedin-title{margin:0 0 7px;font-size:17px;line-height:1.4;overflow-wrap:anywhere}
.linkedin-domain{font-size:12px;color:#666;overflow-wrap:anywhere}
.linkedin-actions{padding:16px;color:#666;font-size:12px;display:flex;justify-content:space-around;gap:10px;flex-wrap:wrap}
:root[data-theme="dark"] .linkedin-post{background:#1b1f23;color:#e8e8e8;border-color:#42474c}
:root[data-theme="dark"] .linkedin-card{background:#28313a}
:root[data-theme="dark"] .linkedin-domain,:root[data-theme="dark"] .linkedin-subtitle,:root[data-theme="dark"] .linkedin-actions{color:#adb4bb}
.preview-tabs{flex-wrap:wrap}.preview-tab{min-width:85px}
.telegram-options{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.telegram-option{font-size:13px;padding:10px 15px;border:1px solid #cad6ce;background:transparent;color:inherit}
.telegram-option[aria-pressed="true"]{background:#168acd;border-color:#168acd;color:#fff}
.telegram-chat{padding:22px;background:#dce8d5;border-radius:12px}
.telegram-message{max-width:460px;margin-left:auto;background:#effdde;color:#172717;border-radius:14px 14px 4px 14px;padding:14px;overflow:hidden}
.telegram-link{display:block;color:#167749;font-size:14px;overflow-wrap:anywhere;text-decoration:none;margin-bottom:12px}
.telegram-embed{border-left:3px solid #49a66f;padding-left:12px;display:flow-root}
.telegram-site{color:#267e48;font-weight:700;font-size:13px;overflow-wrap:anywhere}
.telegram-title{font-weight:700;font-size:15px;line-height:1.4;margin:7px 0;overflow-wrap:anywhere}
.telegram-description{font-size:14px;line-height:1.5;white-space:pre-wrap;overflow-wrap:anywhere;margin:0}
.telegram-image{width:100%;max-height:320px;object-fit:contain;display:block;border-radius:7px;margin-top:12px}
.telegram-message.small .telegram-image{width:76px;height:76px;object-fit:cover;float:right;margin:0 0 8px 12px}
.telegram-time{text-align:right;color:#578465;font-size:11px;margin-top:10px;clear:both}
:root[data-theme="dark"] .telegram-chat{background:#17212b}
:root[data-theme="dark"] .telegram-message{background:#2b5278;color:#e8f0f7}
:root[data-theme="dark"] .telegram-link,:root[data-theme="dark"] .telegram-site{color:#8bcaff}
:root[data-theme="dark"] .telegram-embed{border-color:#6ab5ed}
:root[data-theme="dark"] .telegram-time{color:#a9c9df}
:root[data-theme="dark"] .telegram-option{border-color:#476152}
:root[data-theme="dark"] .telegram-option[aria-pressed="true"]{border-color:#168acd}
@media(max-width:480px){.telegram-chat{padding:12px}}
.network-post{padding:20px;border:1px solid #dce3e9;border-radius:12px;background:#fff;color:#17212b}
.network-author{display:flex;gap:12px;align-items:center;margin-bottom:16px;font-size:14px;font-weight:700}
.network-avatar{width:40px;height:40px;flex-shrink:0;border-radius:50%;display:grid;place-items:center;background:#0085ff;color:#fff;font-size:12px}
.network-handle{font-size:12px;font-weight:400;color:#647485;margin-top:4px}
.network-card{display:block;color:inherit;text-decoration:none;border:1px solid #dce3e9;border-radius:12px;overflow:hidden}
.network-image{width:100%;aspect-ratio:1.91;object-fit:cover;display:block}
.network-copy{padding:16px;min-width:0}.network-title{font-size:16px;font-weight:700;line-height:1.4;margin:0 0 8px;overflow-wrap:anywhere}
.network-description{font-size:13px;line-height:1.5;margin:0 0 10px;overflow-wrap:anywhere;color:#526477}
.network-domain{font-size:12px;color:#647485;overflow-wrap:anywhere}
.network-post.mastodon{background:#f7f7fc}.mastodon .network-avatar{background:#6364ff}
.mastodon .network-card{border-radius:6px}.mastodon .network-image{object-fit:contain;background:#ebecf4}
.network-post.slack{border-radius:8px}.slack .network-avatar{border-radius:8px;background:#611f69}
.slack .network-card{border:0;border-left:4px solid #ccd1d6;border-radius:0}
.slack .network-copy{padding:0 0 0 14px}.slack .network-title{color:#1264a3}
.slack .network-image{width:auto;max-width:100%;height:auto;max-height:260px;aspect-ratio:auto;object-fit:contain;border-radius:7px;margin:12px 0 0 14px}
.slack .network-domain{margin-bottom:8px}.slack .network-description{margin-bottom:0}
:root[data-theme="dark"] .network-post{background:#15202b;color:#e4edf6;border-color:#384958}
:root[data-theme="dark"] .network-card{border-color:#384958}
:root[data-theme="dark"] .network-description,:root[data-theme="dark"] .network-domain,:root[data-theme="dark"] .network-handle{color:#b2c0ce}
:root[data-theme="dark"] .network-post.mastodon{background:#282c37}
:root[data-theme="dark"] .mastodon .network-image{background:#1f232b}
:root[data-theme="dark"] .network-post.slack{background:#1a1d21}
:root[data-theme="dark"] .slack .network-title{color:#6ec8fa}
.slack .network-card{padding-right:14px}
.preview-tabs{display:grid;grid-template-columns:repeat(8,minmax(0,1fr));gap:5px}
.preview-tab{min-width:0;padding:13px 4px;font-size:21px;line-height:1.2}
.preview-tab i{pointer-events:none}
@media(max-width:480px){.preview-tabs{grid-template-columns:repeat(4,minmax(0,1fr))}.preview-tab{font-size:22px;padding:12px 4px}}
.practice-badge{display:inline-block;margin-left:8px;font-size:10px;font-weight:650;border:1px solid currentColor;border-radius:4px;padding:2px 5px;color:#8c6727}:root[data-theme="dark"] .practice-badge{color:#edcc88}
.language-switch{display:flex;gap:3px;border:1px solid #cad6ce;border-radius:8px;padding:3px}.language-switch a{padding:7px 9px;font-size:12px;font-weight:700;color:inherit;text-decoration:none;border-radius:5px}.language-switch a[aria-current]{background:#164f40;color:#fff}:root[data-theme="dark"] .language-switch{border-color:#476152}.header-actions{flex-wrap:wrap;gap:12px}
.preview-tabs{grid-template-columns:repeat(9,minmax(0,1fr))}
.network-post.teams{background:#f5f5f5;border-color:#ddd}
.teams .network-avatar{background:#6264a7}
.teams .network-card{display:flex;align-items:stretch;border-radius:6px;background:#fff;max-width:480px}
.teams .network-image{width:110px;min-width:110px;max-height:160px;aspect-ratio:1;object-fit:cover;align-self:center}
.teams .network-copy{padding:14px}
.teams .network-title{font-size:14px}
.teams .network-description{font-size:12px}
.teams-message-link{display:block;margin:0 0 12px;font-size:14px;color:#5059c9;overflow-wrap:anywhere}
:root[data-theme="dark"] .network-post.teams{background:#202020;border-color:#454545}
:root[data-theme="dark"] .teams .network-card{background:#292929;border-color:#454545}
:root[data-theme="dark"] .teams-message-link{color:#a5adff}
@media(max-width:480px){.preview-tabs{grid-template-columns:repeat(5,minmax(0,1fr))}.teams .network-image{width:76px;min-width:76px}}
.teams-rich .network-card{display:block}.teams-rich .network-image{width:100%;min-width:0;max-height:none;aspect-ratio:1.91;object-fit:cover}
header{display:block;padding:0}
.header-inner{width:100%;max-width:1380px;margin:0 auto;padding:24px 5%;display:flex;align-items:center;justify-content:space-between;gap:24px}
.header-actions{justify-content:flex-end}
@media(max-width:600px){.header-inner{flex-wrap:wrap;gap:16px}.header-actions{margin-left:auto}}
@include('partials.consent-css')
@include('partials.scrollbar-css')
.url-history{margin:12px 0 16px}.history-heading{display:flex;align-items:baseline;justify-content:space-between;gap:12px;flex-wrap:wrap;font-size:12px;color:#63766e}.history-options{flex:0 0 100%;width:100%;min-width:0;margin:0;padding:0;border:0;background:transparent}.history-options summary{font-size:12px;font-weight:400}.history-options-body{width:100%;padding:12px 0}.history-setting{display:flex;align-items:center;gap:8px;font-size:12px}.history-setting input{flex:none;width:16px;height:16px;padding:0;accent-color:#237454}.url-history ul{display:flex;flex-wrap:wrap;gap:8px;list-style:none;padding:0;margin:10px 0;max-height:180px;overflow:auto}.url-history li{display:flex;align-items:center;max-width:100%;background:#e9efe7;border:1px solid #cad6ce;border-radius:24px;overflow:hidden}.url-history button{font-size:12px;padding:7px 12px}.url-history .history-url{min-width:0;max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;background:transparent;color:#17332f;font-weight:500;border-radius:0;padding-right:6px}.url-history .history-remove{padding:7px 10px;background:transparent;color:#63766e;border-radius:0}.url-history .history-url:hover,.url-history .history-remove:hover{background:#d5e4d8}.url-history button:disabled{opacity:.5;cursor:default}.url-history #history-status:empty{display:none}:root[data-theme="dark"] .history-heading{color:#a8bdae}:root[data-theme="dark"] .history-options{background:transparent}:root[data-theme="dark"] .url-history li{background:#192e25;border-color:#476152}:root[data-theme="dark"] .url-history .history-url,:root[data-theme="dark"] .url-history .history-remove{color:#e0eee6}:root[data-theme="dark"] .url-history .history-url:hover,:root[data-theme="dark"] .url-history .history-remove:hover{background:#2b4938}@media(max-width:600px){.url-history .history-url{max-width:calc(100vw - 110px)}}
.link-tools{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:16px}.link-tools button,.link-tools a{font-size:13px;padding:9px 12px;border:1px solid #cad6ce;border-radius:8px;background:transparent;color:inherit;text-decoration:none}.link-tools button:disabled{cursor:default;opacity:.5}.link-tools .hint{flex-basis:100%;line-height:1.6}#share-link-fallback{flex-basis:100%;padding:12px;font-size:13px}:root[data-theme="dark"] .link-tools button,:root[data-theme="dark"] .link-tools a{border-color:#476152}

footer{flex-wrap:wrap;gap:20px}.footer-credit{line-height:1.8}.footer-links{flex-wrap:wrap}
</style>
</head>
<body>
@include('partials.header')
<main>
<div class="eyebrow">{{ __('DEIN TOOL FÜR LINK-VORSCHAUEN') }}</div>
<h1>{{ __('Ein Link. Der erste Eindruck.') }}</h1>
<p class="intro">{{ __('Prüfe die Link-Vorschau und Open-Graph-Angaben deiner Website — für öffentliche Seiten und lokale Projekte.') }}</p>
<form id="form"><input id="url" list="recent-url-suggestions" name="url" type="url" required maxlength="2048" aria-label="{{ __('URL der zu prüfenden Website') }}" placeholder="{{ config('site.example_url') ?: 'https://example.com' }}" autocomplete="url"><button id="submit">Preview ↗</button></form>
<section class="url-history" aria-label="{{ __('Zuletzt geprüft – nur in diesem Browser') }}">
<div class="history-heading"><span>{{ __('Zuletzt geprüft') }}</span><details class="history-options"><summary>{{ __('Verlauf-Einstellungen') }}</summary><div class="history-options-body">
<label class="history-setting"><input id="history-enabled" type="checkbox" checked> {{ __('URL-Verlauf in diesem Browser speichern') }}</label>
<p class="hint">{{ __('Die letzten 20 URLs bleiben auf diesem Gerät. Du kannst einzelne Einträge oder den gesamten Verlauf löschen.') }}</p>
<button type="button" id="clear-url-history">{{ __('Verlauf löschen') }}</button>
</div></details></div>
<ul id="history-list"></ul>
<p class="hint" id="history-status" role="status" aria-live="polite"></p>
</section>
<datalist id="recent-url-suggestions"></datalist>

<div class="hint">{{ __('Unterstützt HTTP und HTTPS. Die Website muss vom Prüfserver aus ohne Anmeldung erreichbar sein; lokal sind auch localhost und .test-Domains möglich.') }}</div>
<div class="link-tools" data-check-base="{{ \App\Support\LocalizedUrl::to() }}">
<button type="button" id="copy-check-link" disabled><i class="fa-solid fa-link" aria-hidden="true"></i> {{ __('Prüf-Link kopieren') }}</button>
<a id="preview-bookmarklet" href="#" draggable="true"><i class="fa-solid fa-bookmark" aria-hidden="true"></i> {{ __('Link Preview prüfen') }}</a>
<span class="hint">{{ __('„Link Preview prüfen“ in die Lesezeichenleiste ziehen und auf der gewünschten Website anklicken. Links kannst du auch im URL-Feld ablegen.') }}</span>
<span id="share-status" class="hint" role="status" aria-live="polite"></span>
<input id="share-link-fallback" type="text" readonly hidden aria-label="{{ __('Teilbarer Prüf-Link') }}">
</div>

<div id="error" class="error" role="alert" hidden></div>
<div class="layout" aria-live="polite" aria-busy="false" id="results">
<section class="panel"><div class="panel-head"><h2>{{ __('Link-Vorschau') }}</h2><span class="muted">{{ __('SIMULATION') }}</span></div>
<div class="preview-tabs" role="tablist" aria-label="{{ __('Vorschau auswählen') }}">
<button type="button" id="tab-og" class="preview-tab" role="tab" aria-selected="true" aria-controls="pane-og" tabindex="0" aria-label="Open Graph" title="Open Graph"><i class="fa-solid fa-share-nodes" aria-hidden="true"></i></button>
<button type="button" id="tab-discord" class="preview-tab" role="tab" aria-selected="false" aria-controls="pane-discord" tabindex="-1" aria-label="Discord" title="Discord"><i class="fa-brands fa-discord" aria-hidden="true"></i></button>
<button type="button" id="tab-twitter" class="preview-tab" role="tab" aria-selected="false" aria-controls="pane-twitter" tabindex="-1" aria-label="Twitter / X" title="Twitter / X"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></button>
<button type="button" id="tab-linkedin" class="preview-tab" role="tab" aria-selected="false" aria-controls="pane-linkedin" tabindex="-1" aria-label="LinkedIn" title="LinkedIn"><i class="fa-brands fa-linkedin" aria-hidden="true"></i></button>
<button type="button" id="tab-telegram" class="preview-tab" role="tab" aria-selected="false" aria-controls="pane-telegram" tabindex="-1" aria-label="Telegram" title="Telegram"><i class="fa-brands fa-telegram" aria-hidden="true"></i></button>
<button type="button" id="tab-bluesky" class="preview-tab" role="tab" aria-selected="false" aria-controls="pane-bluesky" tabindex="-1" aria-label="Bluesky" title="Bluesky"><i class="fa-brands fa-bluesky" aria-hidden="true"></i></button>
<button type="button" id="tab-mastodon" class="preview-tab" role="tab" aria-selected="false" aria-controls="pane-mastodon" tabindex="-1" aria-label="Mastodon" title="Mastodon"><i class="fa-brands fa-mastodon" aria-hidden="true"></i></button>
<button type="button" id="tab-slack" class="preview-tab" role="tab" aria-selected="false" aria-controls="pane-slack" tabindex="-1" aria-label="Slack" title="Slack"><i class="fa-brands fa-slack" aria-hidden="true"></i></button>
<button type="button" id="tab-teams" class="preview-tab" role="tab" aria-selected="false" aria-controls="pane-teams" tabindex="-1" aria-label="Microsoft Teams" title="Microsoft Teams"><i class="fa-brands fa-microsoft" aria-hidden="true"></i></button>
</div>
<div id="pane-og" role="tabpanel" aria-labelledby="tab-og" tabindex="0"><div id="preview" class="empty"><div><span style="font-size:40px">↗</span><strong>{{ __('Dein Link verdient einen guten Auftritt.') }}</strong><span style="font-size:13px">{{ __('URL eingeben und Vorschau starten.') }}</span></div></div><p class="footnote">{{ __('Die Vorschau simuliert eine generische Linkkarte. Darstellung und Zuschnitt können je nach Plattform abweichen. Es wird das vom Server gelieferte HTML geprüft.') }}</p>
</div><div id="pane-discord" role="tabpanel" aria-labelledby="tab-discord" tabindex="0" hidden>
<div id="discord-preview" class="empty"><div><strong>{{ __('Dein Link im Discord-Chat.') }}</strong><span style="font-size:13px">{{ __('Die Discord-Vorschau erscheint nach der Prüfung.') }}</span></div></div>
<p class="footnote">{{ __('Annäherung an einen Discord-Link-Embed mit den ausgelesenen Metadaten. Lokale Adressen sind für Discord selbst nicht erreichbar. Caching, Einstellungen und spezielle Website-Integrationen können die tatsächliche Darstellung verändern.') }}</p></div>
<div id="pane-twitter" role="tabpanel" aria-labelledby="tab-twitter" tabindex="0" hidden>
<div id="twitter-preview" class="empty"><div><strong>{{ __('Dein Link auf Twitter / X.') }}</strong><span style="font-size:13px">{{ __('Twitter-Cards und Open-Graph-Fallbacks werden nach der Prüfung angezeigt.') }}</span></div></div>
<p id="twitter-card-info" class="footnote"></p>
<p class="footnote">{{ __('Simulation der klassischen Cards-Layouts. Die Darstellung auf X kann je nach Client abweichen. Lokale URLs sind für X selbst nicht erreichbar. Player- und App-Karten werden nur statisch dargestellt.') }}</p>
</div><div id="pane-linkedin" role="tabpanel" aria-labelledby="tab-linkedin" tabindex="0" hidden>
<div id="linkedin-preview" class="empty"><div><strong>{{ __('Dein Link auf LinkedIn.') }}</strong><span style="font-size:13px">{{ __('Die Vorschau erscheint nach der Prüfung.') }}</span></div></div>
<p class="footnote">{{ __('Simulation einer LinkedIn-Karte mit großem Bild. LinkedIn verwendet Open Graph. Die tatsächliche Darstellung entscheidet LinkedIn; ein großes Bild kann nicht per Metatag erzwungen werden. Lokale URLs sind für LinkedIn nicht erreichbar.') }}</p>
<p class="footnote"><a href="{{ \App\Support\ExternalUrl::to('https://www.linkedin.com/post-inspector/', 'linkedin_inspector') }}" target="_blank" rel="noopener noreferrer">{{ __('Öffentliche URL im LinkedIn Post Inspector prüfen ↗') }}</a></p>
</div><div id="pane-telegram" role="tabpanel" aria-labelledby="tab-telegram" tabindex="0" hidden>
<div class="telegram-options" role="group" aria-label="{{ __('Telegram-Bildgröße') }}">
<button type="button" class="telegram-option" data-size="small" aria-pressed="false">{{ __('Kleines Bild') }}</button>
<button type="button" class="telegram-option" data-size="large" aria-pressed="true">{{ __('Großes Bild') }}</button>
</div>
<div id="telegram-preview" class="empty"><div><strong>{{ __('Dein Link im Telegram-Chat.') }}</strong><span style="font-size:13px">{{ __('Die Vorschau erscheint nach der Prüfung.') }}</span></div></div>
<p class="footnote">{{ __('Simulation anhand der ausgelesenen Metadaten. In Telegram können Nutzer die Bildgröße wählen; die Website kann diese Auswahl nicht per Open-Graph-Tag erzwingen. Lokale URLs sind für Telegram selbst nicht erreichbar. Darstellung und Cache können vom Original abweichen.') }}</p>
</div>
<div id="pane-bluesky" role="tabpanel" aria-labelledby="tab-bluesky" tabindex="0" hidden><div id="bluesky-preview" class="empty"><div><strong>{{ __('Dein Link auf Bluesky.') }}</strong><span style="font-size:13px">{{ __('Die Vorschau erscheint nach der Prüfung.') }}</span></div></div><p class="footnote">{{ __('Simulation einer Standard-Linkkarte. Darstellung, Einstellungen und Cache können je nach Client abweichen. Lokale URLs sind für Bluesky selbst nicht erreichbar.') }}</p></div>
<div id="pane-mastodon" role="tabpanel" aria-labelledby="tab-mastodon" tabindex="0" hidden><div id="mastodon-preview" class="empty"><div><strong>{{ __('Dein Link auf Mastodon.') }}</strong><span style="font-size:13px">{{ __('Die Vorschau erscheint nach der Prüfung.') }}</span></div></div><p class="footnote">{{ __('Simulation einer Standard-Linkkarte. Darstellung, Einstellungen und Cache können je nach Client abweichen. Lokale URLs sind für Mastodon selbst nicht erreichbar.') }}</p></div>
<div id="pane-slack" role="tabpanel" aria-labelledby="tab-slack" tabindex="0" hidden><div id="slack-preview" class="empty"><div><strong>{{ __('Dein Link auf Slack.') }}</strong><span style="font-size:13px">{{ __('Die Vorschau erscheint nach der Prüfung.') }}</span></div></div><p class="footnote">{{ __('Simulation einer Standard-Linkkarte. Darstellung, Einstellungen und Cache können je nach Client abweichen. Lokale URLs sind für Slack selbst nicht erreichbar.') }}</p></div><div id="pane-teams" role="tabpanel" aria-labelledby="tab-teams" tabindex="0" hidden>
<div id="teams-preview" class="empty"><div><strong>{{ __('Dein Link in Microsoft Teams.') }}</strong><span style="font-size:13px">{{ __('Die Vorschau erscheint nach der Prüfung.') }}</span></div></div>
<p class="footnote">{{ __('Teams-Simulation: Erkannte JSON-LD-Produktdaten werden als große Bildkarte dargestellt, sonst wird die kompakte Open-Graph-Karte gezeigt. Teams-Apps und andere Rich Cards werden nicht simuliert. Lokale URLs sind für Teams selbst nicht erreichbar.') }}</p>
</div></section>
<section class="panel"><div class="panel-head checks-heading"><h2 id="checks-title">{{ __('Open-Graph-Prüfung') }}</h2><div id="counts" class="status-counts"><span class="muted">{{ __('NOCH KEINE PRÜFUNG') }}</span></div></div><div id="checks" class="empty"><div><strong>{{ __('Was stimmt? Was fehlt?') }}</strong><span style="font-size:13px">{{ __('Pflichtfelder, Empfehlungen und Bildprüfung') }}<br>{{ __('erscheinen hier nach deiner Anfrage.') }}</span></div></div></section>
</div>
<details id="metadata" hidden><summary>{{ __('Ausgelesene Metadaten ansehen') }}</summary><pre id="tags"></pre></details>
<footer>@include('partials.footer-credit')<div class="footer-links"><a href="{{ \App\Support\LocalizedUrl::to('/impressum') }}">{{ __('Impressum') }}</a>
<a href="{{ \App\Support\LocalizedUrl::to('/datenschutz') }}">{{ __('Datenschutz') }}</a>
<button type="button" class="footer-button" data-analytics-settings>{{ __('Analyse-Einstellungen') }}</button><a href="{{ \App\Support\ExternalUrl::to('https://github.com/kuhla-tech/open-graph-preview', 'footer_repository') }}" target="_blank" rel="noopener noreferrer">{{ __('GitHub Repository ↗') }}</a><a href="{{ \App\Support\ExternalUrl::to('https://ogp.me/', 'footer_documentation') }}" target="_blank" rel="noopener noreferrer">{{ __('Open Graph Protokoll ↗') }}</a></div></footer>
</main>
<script>
let currentPreviewData = null;
const previewTabs = [...document.querySelectorAll('.preview-tab')];
function selectPreviewTab(selected, focus = false) {
    for (const tab of previewTabs) {
        const active = tab === selected;
        tab.setAttribute('aria-selected', String(active));
        tab.tabIndex = active ? 0 : -1;
        document.getElementById(tab.getAttribute('aria-controls')).hidden = !active;
    }
    if (currentPreviewData) renderChecks(currentPreviewData);
    if (focus) selected.focus();
}
previewTabs.forEach((tab, index) => {
    tab.addEventListener('click', () => selectPreviewTab(tab));
    tab.addEventListener('keydown', event => {
        let next;
        if (event.key === 'ArrowRight') next = (index + 1) % previewTabs.length;
        if (event.key === 'ArrowLeft') next = (index - 1 + previewTabs.length) % previewTabs.length;
        if (event.key === 'Home') next = 0;
        if (event.key === 'End') next = previewTabs.length - 1;
        if (next !== undefined) {
            event.preventDefault();
            selectPreviewTab(previewTabs[next], true);
        }
    });
});
const themeToggle = document.querySelector('#theme-toggle');
function updateThemeToggle() {
    const dark = document.documentElement.dataset.theme === 'dark';
    themeToggle.setAttribute('aria-pressed', String(dark));
    themeToggle.textContent = dark ? t('☀ Light-Mode') : t('☾ Dark-Mode');
    themeToggle.setAttribute('aria-label', dark ? t('Zum Light-Mode wechseln') : t('Zum Dark-Mode wechseln'));
}
updateThemeToggle();
themeToggle.addEventListener('click', () => {
    const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = theme;
    try { localStorage.setItem('og-theme', theme); } catch {}
    updateThemeToggle();
});
matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
    let saved;
    try { saved = localStorage.getItem('og-theme'); } catch {}
    if (!['dark', 'light'].includes(saved)) {
        document.documentElement.dataset.theme = event.matches ? 'dark' : 'light';
        updateThemeToggle();
    }
});
const form=document.querySelector('#form'),button=document.querySelector('#submit'),error=document.querySelector('#error'),preview=document.querySelector('#preview'),checks=document.querySelector('#checks');
try { sessionStorage.removeItem('og-last-url'); } catch {}
function element(tag,text,cls){const e=document.createElement(tag);if(text)e.textContent=text;if(cls)e.className=cls;return e}
function renderDiscord(data) {
    const container = document.querySelector('#discord-preview');
    container.className = 'discord-chat';
    container.replaceChildren(element('div', t('DU'), 'discord-avatar'));
    const message = element('div', null, 'discord-message');
    const user = element('div', t('Du'), 'discord-user');
    user.append(element('span', t('Heute um ') + new Date().toLocaleTimeString(document.documentElement.lang === 'en' ? 'en-GB' : 'de-DE', {hour:'2-digit', minute:'2-digit'}), 'discord-time'));
    const link = element('a', data.url, 'discord-link');
    link.href = outboundUrl(data.url, 'preview_discord'); link.target = '_blank'; link.rel = 'noopener noreferrer';
    const embed = element('div', null, 'discord-embed');
    const color = data.tags['theme-color']?.[0] || '';
    if (/^#[0-9a-f]{6}$/i.test(color) || /^#[0-9a-f]{3}$/i.test(color)) embed.style.borderLeftColor = color;
    const provider = data.tags['og:site_name']?.[0];
    if (provider) embed.append(element('div', provider, 'discord-provider'));
    const title = element('a', data.title, 'discord-title');
    title.href = outboundUrl(data.url, 'preview_discord'); title.target = '_blank'; title.rel = 'noopener noreferrer';
    embed.append(title);
    if (data.description) embed.append(element('p', data.description, 'discord-description'));
    if (data.image) {
        const image = element('img', null, 'discord-image');
        image.src = data.image;
        image.alt = data.tags['og:image:alt']?.[0] || t('Discord Embed-Bild');
        embed.append(image);
    }
    message.append(user, link, embed);
    container.append(message);
}
function renderTwitter(data) {
    const twitter = data.twitter;
    const container = document.querySelector('#twitter-preview');
    const info = document.querySelector('#twitter-card-info');
    info.textContent = twitter.card ? t('Kartentyp: ') + twitter.card + (twitter.inferred ? t(' · aus Open Graph abgeleitet (mögliches Fallback)') : '') : t('Kein Kartentyp erkannt.');
    container.replaceChildren();
    if (!twitter.previewable) {
        container.className = 'empty';
        container.append(element('div', t('Keine Twitter/X-Karte ableitbar. Details findest du in der Twitter/X-Prüfung.')));
        return;
    }
    container.className = 'twitter-post';
    const user = element('div', t('Du'), 'twitter-user');
    user.append(element('span', t('@du · gerade eben')));
    const card = element('a', null, 'twitter-card ' + twitter.card);
    card.href = outboundUrl(data.url, 'preview_twitter'); card.target = '_blank'; card.rel = 'noopener noreferrer';
    if (twitter.image) {
        const image = element('img', null, 'twitter-image');
        image.src = twitter.image; image.alt = twitter.alt || t('Twitter/X Card-Bild');
        card.append(image);
    }
    if (twitter.card === 'player' || twitter.card === 'app') {
        card.append(element('div', twitter.card === 'player' ? t('▶ Player-Karte · statische Vorschau') : t('App-Karte · statische Vorschau'), 'twitter-special'));
    }
    const copy = element('div', null, 'twitter-copy');
    copy.append(element('div', twitter.domain, 'twitter-domain'));
    if (twitter.title) copy.append(element('h3', twitter.title, 'twitter-title'));
    if (twitter.description) copy.append(element('p', twitter.description, 'twitter-description'));
    card.append(copy);
    container.append(user, card);
}
function renderLinkedIn(data) {
    const container = document.querySelector('#linkedin-preview');
    container.className = 'linkedin-post'; container.replaceChildren();
    const author = element('div', null, 'linkedin-author');
    const user = element('div', t('Du'));
    user.append(element('span', t('Gerade eben · Öffentlich'), 'linkedin-subtitle'));
    author.append(element('span', t('DU'), 'linkedin-avatar'), user);
    const card = element('a', null, 'linkedin-card');
    card.href = outboundUrl(data.url, 'preview_linkedin'); card.target = '_blank'; card.rel = 'noopener noreferrer';
    if (data.image) {
        const image = element('img'); image.src = data.image;
        image.alt = data.tags['og:image:alt']?.[0] || t('LinkedIn Linkbild');
        card.append(image);
    }
    const copy = element('div', null, 'linkedin-copy');
    copy.append(element('h3', data.title, 'linkedin-title'), element('div', new URL(data.url).hostname, 'linkedin-domain'));
    card.append(copy);
    const actions = element('div', null, 'linkedin-actions');
    for (const text of [t('Gefällt mir'), t('Kommentieren'), t('Reposten'), t('Senden')]) actions.append(element('span', text));
    container.append(author, card, actions);
}
let telegramSize = 'large';
document.querySelectorAll('.telegram-option').forEach(option => {
    option.addEventListener('click', () => {
        telegramSize = option.dataset.size;
        document.querySelectorAll('.telegram-option').forEach(button => button.setAttribute('aria-pressed', String(button === option)));
        if (currentPreviewData) renderTelegram(currentPreviewData);
    });
});
function renderTelegram(data) {
    const container = document.querySelector('#telegram-preview');
    container.className = 'telegram-chat'; container.replaceChildren();
    const message = element('div', null, 'telegram-message ' + telegramSize);
    const link = element('a', data.url, 'telegram-link');
    link.href = outboundUrl(data.url, 'preview_telegram'); link.target = '_blank'; link.rel = 'noopener noreferrer';
    const embed = element('div', null, 'telegram-embed');
    const image = data.image ? element('img', null, 'telegram-image') : null;
    if (image) {
        image.src = data.image;
        image.alt = data.tags['og:image:alt']?.[0] || t('Telegram-Vorschaubild');
        if (telegramSize === 'small') embed.append(image);
    }
    embed.append(element('div', data.site, 'telegram-site'), element('div', data.title, 'telegram-title'));
    if (data.description) embed.append(element('p', data.description, 'telegram-description'));
    if (image && telegramSize === 'large') embed.append(image);
    message.append(link, embed, element('div', new Date().toLocaleTimeString(document.documentElement.lang === 'en' ? 'en-GB' : 'de-DE', {hour:'2-digit', minute:'2-digit'}) + ' ✓✓', 'telegram-time'));
    container.append(message);
}
const extraNetworks = {
    bluesky: {name:'Bluesky', avatar:'BS', handle:t('@du.bsky.social'), detail:t('Standard-Linkkarte simuliert. Bluesky speichert Linkkarten im Beitrag; Änderungen an der Website müssen eine vorhandene Karte nicht aktualisieren.')},
    mastodon: {name:'Mastodon', avatar:'M', handle:t('@du@mastodon.example'), detail:t('Standard-Linkkarte simuliert. Instanz, Client und Cache beeinflussen die Darstellung; serverseitige Bilder werden nicht grundsätzlich zugeschnitten.')},
    teams: {name:'Microsoft Teams', avatar:'T', handle:t('Heute · 12:00'), detail:t('Standard-URL-Vorschau simuliert. Teams kann über Apps, Adaptive Cards oder strukturierte Website-Daten eine andere Karte anzeigen; Einstellungen und Cache beeinflussen die Vorschau.')},
    slack: {name:'Slack', avatar:'S', handle:t('Heute · 12:00'), detail:t('Klassische Link-Vorschau simuliert. Slack-Apps können eigene Vorschauen liefern; Workspace- und Nutzereinstellungen können Vorschauen deaktivieren.')}
};
function renderExtraNetwork(data, platform) {
    const config = extraNetworks[platform];
    const container = document.querySelector('#' + platform + '-preview');
    if (platform === 'teams' && data.schema?.preview) data = {...data, ...data.schema.preview};
    container.className = 'network-post ' + platform + (platform === 'teams' && data.schema?.preview ? ' teams-rich' : ''); container.replaceChildren();
    const author = element('div', null, 'network-author');
    const user = element('div', t('Du'));
    user.append(element('div', config.handle, 'network-handle'));
    author.append(element('span', config.avatar, 'network-avatar'), user);
    const card = element('a', null, 'network-card');
    card.href = outboundUrl(data.url, 'preview_' + platform); card.target = '_blank'; card.rel = 'noopener noreferrer';
    const copy = element('div', null, 'network-copy');
    const domain = element('div', new URL(data.url).hostname, 'network-domain');
    if (platform === 'slack') copy.append(domain);
    copy.append(element('h3', data.title, 'network-title'));
    if (data.description) copy.append(element('p', data.description, 'network-description'));
    if (platform !== 'slack') copy.append(domain);
    const image = data.image ? element('img', null, 'network-image') : null;
    if (image) { image.src = data.image; image.alt = data.tags['og:image:alt']?.[0] || config.name + t(' Linkbild'); }
    if (platform === 'slack') { card.append(copy); if (image) card.append(image); }
    else { if (image) card.append(image); card.append(copy); }
    if (platform === 'teams') {
        const link = element('a', data.url, 'teams-message-link');
        link.href = outboundUrl(data.url, 'preview_' + platform); link.target = '_blank'; link.rel = 'noopener noreferrer';
        container.append(author, link, card);
    } else {
        container.append(author, card);
    }
}
function renderChecks(data) {
    const twitterSelected = document.querySelector('#tab-twitter').getAttribute('aria-selected') === 'true';
    const linkedInSelected = document.querySelector('#tab-linkedin').getAttribute('aria-selected') === 'true';
    const telegramSelected = document.querySelector('#tab-telegram').getAttribute('aria-selected') === 'true';
    const telegramChecks = [...data.checks, {label:t('Telegram-Bildgröße'),status:'warn',detail:t('Kleine oder große Medien werden vom Nutzer in Telegram gewählt. Die Simulation erzwingt keine Darstellung auf Telegram.')}];
    const extraSelected = Object.keys(extraNetworks).find(platform => document.querySelector('#tab-' + platform).getAttribute('aria-selected') === 'true');
    const schemaChecks = data.schema?.checks || [];
    const extraChecks = extraSelected ? [...data.checks, {label:extraNetworks[extraSelected].name + t('-Darstellung'),status:'warn',detail:extraNetworks[extraSelected].detail}] : data.checks;
    const selectedChecks = twitterSelected ? data.twitter.checks : linkedInSelected ? data.linkedin_checks : telegramSelected ? telegramChecks : extraChecks;
    const list = [...selectedChecks, ...schemaChecks];
    document.querySelector('#checks-title').textContent = twitterSelected ? t('Twitter/X-Prüfung') : linkedInSelected ? t('LinkedIn-Prüfung') : telegramSelected ? t('Telegram-Prüfung') : extraSelected ? extraNetworks[extraSelected].name + t('-Prüfung') : t('Open-Graph-Prüfung');
    checks.replaceChildren(); checks.className = '';
    for (const check of list) {
        const row = element('div', null, 'check ' + check.status);
        row.append(element('span', check.status === 'pass' ? '✓' : check.status === 'fail' ? '×' : '!', 'icon'));
        const content = element('div');
        content.append(element('b', check.label));
        if (check.category === 'best_practice') content.append(element('span', 'Best Practice', 'practice-badge'));
        content.append(element('p', check.detail));
        row.append(content); checks.append(row);
    }
    const count = status => list.filter(check => check.status === status).length;
    const counts = document.querySelector('#counts');
    counts.replaceChildren();
    for (const [status, icon, label] of [['pass', '✓', 'OK'], ['warn', '⚠', t('Hinweise')], ['fail', '×', t('Fehler')]]) {
        const badge = element('span', null, 'status-count ' + status);
        const symbol = element('span', icon, 'status-count-icon');
        symbol.setAttribute('aria-hidden', 'true');
        badge.append(symbol, element('span', count(status) + ' ' + label));
        counts.append(badge);
    }
}
form.addEventListener('submit',async event=>{
event.preventDefault();if(button.disabled)return;button.disabled=true;button.textContent=t('Wird geprüft …');error.hidden=true;document.querySelector('#results').setAttribute('aria-busy','true');
try{
document.dispatchEvent(new CustomEvent('og:check-started', {detail:{url:document.querySelector('#url').value.trim()}}));
const response=await fetch('{{ \App\Support\LocalizedUrl::to("/preview") }}',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({url:document.querySelector('#url').value})});
const data=await response.json();if(!response.ok)throw new Error(data.message||t('Die Prüfung ist fehlgeschlagen.'));
currentPreviewData = data;
renderDiscord(data);
renderTwitter(data);
renderLinkedIn(data);
renderTelegram(data);
for (const platform of Object.keys(extraNetworks)) renderExtraNetwork(data, platform);
preview.replaceChildren();preview.className='card';
if(data.image){const img=element('img');img.src=data.image;img.alt=data.tags['og:image:alt']?.[0]||t('Open Graph Vorschau');preview.append(img)}else preview.append(element('div',t('Kein Vorschaubild verfügbar'),'no-image'));
const body=element('div',null,'card-body');body.append(element('div',data.site,'site'),element('h3',data.title),element('p',data.description||t('Keine Beschreibung vorhanden.')));preview.append(body);
renderChecks(data);
document.querySelector('#tags').textContent=JSON.stringify({fetched_url:data.url,html_fallback_used:data.fallback,metadata:data.tags,schema_products:data.schema?.products},null,2);
document.querySelector('#metadata').hidden=false;
}catch(e){currentPreviewData=null;for(const platform of Object.keys(extraNetworks)){const container=document.querySelector('#'+platform+'-preview');container.className='empty';container.replaceChildren(element('div',t('Keine ')+extraNetworks[platform].name+t('-Vorschau für diese Anfrage.')));}const telegram=document.querySelector('#telegram-preview');telegram.className='empty';telegram.replaceChildren(element('div',t('Keine Telegram-Vorschau für diese Anfrage.')));const linkedin=document.querySelector('#linkedin-preview');linkedin.className='empty';linkedin.replaceChildren(element('div',t('Keine LinkedIn-Vorschau für diese Anfrage.')));const twitter=document.querySelector('#twitter-preview');twitter.className='empty';twitter.replaceChildren(element('div',t('Keine Twitter/X-Vorschau für diese Anfrage.')));document.querySelector('#twitter-card-info').textContent='';const discord=document.querySelector('#discord-preview');discord.className='empty';discord.replaceChildren(element('div',t('Keine Discord-Vorschau für diese Anfrage.')));error.textContent=e.message;error.hidden=false;preview.replaceChildren(element('div',t('Keine aktuelle Vorschau: Die Anfrage ist fehlgeschlagen.')));preview.className='empty';checks.replaceChildren(element('div',t('Keine Prüfergebnisse für diese Anfrage.')));checks.className='empty';document.querySelector('#counts').replaceChildren(element('span',t('PRÜFUNG FEHLGESCHLAGEN'),'status-count fail'));document.querySelector('#metadata').hidden=true}
finally{button.disabled=false;button.textContent='Preview ↗';document.querySelector('#results').setAttribute('aria-busy','false')}
});
</script>
<script src="{{ asset('js/url-history.js') }}" defer></script>
<script src="{{ asset('js/preview-links.js') }}" defer></script>
@include('partials.analytics')
</body></html>




























