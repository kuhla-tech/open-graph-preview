@extends('admin.layout')
@section('content')<div class="panel"><h1>{{ __(['general'=>'Allgemein','branding'=>'Branding und Bilder','search'=>'Suchmaschinen','monitoring'=>'Monitoring und Logs','legal'=>'Rechtliche Angaben','pages'=>'Rechtstext-Editor'][$section]) }}</h1>
<p class="hint">{{ __('Hier gespeicherte Werte überschreiben die .env-Vorgaben dieser Instanz. APP_KEY, Domain, Datenbank, Cache, Session-Treiber und private Prüfziele bleiben in der Server-Konfiguration.') }}</p>
@if($section === 'pages')<p>{{ __('Die Texte werden als sicherer Klartext mit Zeilenumbrüchen veröffentlicht. Leere Felder verwenden die Vorlage aus den rechtlichen Angaben. Beide Sprachversionen werden unabhängig gespeichert.') }}</p><p><a href="{{ \App\Support\LocalizedUrl::to('/impressum') }}" target="_blank" rel="noopener">{{ __('Impressum ansehen') }}</a> · <a href="{{ \App\Support\LocalizedUrl::to('/datenschutz') }}" target="_blank" rel="noopener">{{ __('Datenschutz ansehen') }}</a></p>@endif
@if($section === 'legal')<p class="hint">{{ __('Die angegebenen Aufbewahrungsfristen müssen im Webserver, in Matomo und Sentry entsprechend konfiguriert sein. Diese Angaben ändern keine externen Dienste.') }}</p><p>{{ __('Trage die Angaben des tatsächlichen Betreibers dieser Instanz ein. Zusätzliche Pflichtangaben und eigene Rechtstexte können im Rechtstext-Editor ergänzt werden.') }}</p>@endif
<form method="post" action="{{ route('admin.settings.update', $section) }}" enctype="multipart/form-data">@csrf
@foreach($fields as $key => $field)
<label for="{{ $key }}">@if($field['type']==='checkbox')<input type="checkbox" id="{{ $key }}" name="{{ $key }}" value="1" @checked(old($key, $values[$key]))>@endif{{ __($field['label']) }}</label>
@if($field['type']==='textarea')<textarea id="{{ $key }}" name="{{ $key }}" rows="{{ $section === 'pages' ? 18 : 4 }}" class="{{ $section === 'pages' ? 'page-editor' : '' }}">{{ old($key, $values[$key]) }}</textarea>
@elseif($field['type']==='select')<select id="{{ $key }}" name="{{ $key }}">@foreach($key==='app_locale' ? ['de','en'] : ($key==='log_channel' ? ['single','daily'] : ['debug','info','notice','warning','error','critical','alert','emergency']) as $option)<option value="{{ $option }}" @selected(old($key, $values[$key])===$option)>{{ strtoupper($option) }}</option>@endforeach</select>
@elseif($field['type']==='secret')<input id="{{ $key }}" type="password" name="{{ $key }}" value="" autocomplete="new-password" placeholder="{{ $values[$key] ? __('Bereits hinterlegt – leer lassen zum Beibehalten') : __('Noch nicht hinterlegt') }}"><label><input type="checkbox" name="clear_{{ $key }}" value="1">{{ __('Gespeicherten Wert entfernen') }}</label>
@elseif($field['type']!=='checkbox')<input id="{{ $key }}" name="{{ $key }}" type="{{ $field['type'] }}" value="{{ old($key, $values[$key]) }}" @if($field['type']==='number') min="1" @endif>
@endif
@error($key)<p class="field-error">{{ $message }}</p>@enderror
@endforeach
@if($section === 'branding')
@foreach(['logo'=>'Logo', 'social_image'=>'Open-Graph-Bild', 'icon'=>'Fav-/App-Icon'] as $kind => $label)
<label for="{{ $kind }}">{{ __($label) }}</label><p class="hint">{{ $kind==='social_image' ? __('Wird auf 1200 × 630 Pixel zugeschnitten.') : ($kind==='icon' ? __('Quadratische Vorlage empfohlen. Favicon und App-Icons werden automatisch erzeugt.') : __('PNG mit transparentem Hintergrund empfohlen.')) }} {{ __('PNG, JPEG oder WebP; maximal 8 MB und 20 Megapixel.') }}</p>
@if(config('branding.'.($kind==='icon' ? 'icon_192' : $kind)))<img class="asset-preview" src="{{ url(config('branding.'.($kind==='icon' ? 'icon_192' : $kind))) }}" alt="{{ __($label) }}">@endif
<input id="{{ $kind }}" type="file" name="{{ $kind }}" accept="image/png,image/jpeg,image/webp"><label><input type="checkbox" name="clear_{{ $kind }}" value="1">{{ __('Eigenes Bild entfernen und Standard verwenden') }}</label>
@endforeach
@endif
<div class="actions"><button>{{ __('Speichern') }}</button></div></form></div>@endsection
