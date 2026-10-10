@if(config('search.google_verification'))
<meta name="google-site-verification" content="{{ config('search.google_verification') }}">
@endif
@if(config('search.bing_verification'))
<meta name="msvalidate.01" content="{{ config('search.bing_verification') }}">
@endif
