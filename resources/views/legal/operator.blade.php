<p>
@if(config('legal.controller_name')){{ config('legal.controller_name') }}<br>@endif
@if(config('legal.brand')){{ config('legal.brand') }}<br>@endif
@if(config('legal.address')){{ config('legal.address') }}<br>@endif
@if(config('legal.postcode_city')){{ config('legal.postcode_city') }}<br>@endif
@if(config('legal.country')){{ __(config('legal.country')) }}<br>@endif
@if(config('legal.email'))<a href="mailto:{{ config('legal.email') }}">{{ config('legal.email') }}</a><br>@endif
@if(config('legal.phone')){{ __('Telefon') }}: {{ config('legal.phone') }}@endif
</p>
