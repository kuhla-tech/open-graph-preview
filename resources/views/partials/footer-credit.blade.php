@php
    $ownerName = config('branding.owner_name');
    $hostingName = config('branding.hosting_name');
    $validLink = fn ($url) => is_string($url) && filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
@endphp
@if($ownerName || $hostingName)
<span class="footer-credit">
@if($ownerName)
{{ __(':project ist ein Projekt von', ['project' => config('app.name')]) }}
@if($validLink(config('branding.owner_url')))<a href="{{ \App\Support\ExternalUrl::to(config('branding.owner_url'), 'footer_project') }}" target="_blank" rel="noopener noreferrer">{{ $ownerName }}</a>@else{{ $ownerName }}@endif
@endif
@if($ownerName && $hostingName)<br>@endif
@if($hostingName)
{{ __('Gehostet bei') }}
@if($validLink(config('branding.hosting_url')))<a href="{{ \App\Support\ExternalUrl::to(config('branding.hosting_url'), 'footer_hosting') }}" target="_blank" rel="noopener noreferrer">{{ $hostingName }}</a>@else{{ $hostingName }}@endif
@endif
</span>
@endif
