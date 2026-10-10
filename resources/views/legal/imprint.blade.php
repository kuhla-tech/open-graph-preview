@extends('legal.layout', ['pageTitle' => __('Impressum')])
@section('content')
<h1>{{ __('Impressum') }}</h1>
@if(config('pages.imprint_'.app()->getLocale()))
<div class="legal-text">{{ config('pages.imprint_'.app()->getLocale()) }}</div>
@else
@if(config('legal.controller_name') || config('legal.brand'))
<p>{{ __(':project ist ein Projekt von', ['project' => config('app.name')]) }} {{ config('legal.brand') ?: config('legal.controller_name') }}</p>
@include('legal.operator')
@if(config('legal.extra'))<div class="legal-text">{{ config('legal.extra') }}</div>@endif
@else<p>{{ __('Der Betreiber dieser Instanz hat noch keine rechtlichen Angaben hinterlegt.') }}</p>@endif
@endif
@endsection
