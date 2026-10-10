@php
    $lightLogo = config('branding.logo_light') ?: config('branding.logo') ?: config('branding.logo_dark');
    $darkLogo = config('branding.logo_dark') ?: $lightLogo;
@endphp
@if($lightLogo)
<span class="brand-artwork"><img class="brand-logo-light" src="{{ url($lightLogo) }}" alt="{{ config('app.name') }}"><img class="brand-logo-dark" src="{{ url($darkLogo) }}" alt="{{ config('app.name') }}"></span>
@else
<span class="logo">og</span>{{ config('app.name') }}
@endif
<style>
.brand-artwork{display:inline-flex;align-items:center;vertical-align:middle}.brand-artwork img{display:block;width:auto;height:40px;max-width:280px;object-fit:contain}.brand-artwork .brand-logo-dark{display:none}:root[data-theme="dark"] .brand-artwork .brand-logo-light{display:none}:root[data-theme="dark"] .brand-artwork .brand-logo-dark{display:block}@media(max-width:600px){.brand-artwork img{max-width:220px}}
</style>
