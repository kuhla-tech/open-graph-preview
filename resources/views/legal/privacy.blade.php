@extends('legal.layout', ['pageTitle' => __('Datenschutzerklärung')])
@section('content')
<h1>{{ __('Datenschutzerklärung') }}</h1>
@if(config('pages.privacy_'.app()->getLocale()))
<div class="legal-text">{{ config('pages.privacy_'.app()->getLocale()) }}</div>
@else
<h2>{{ __('Verantwortlicher') }}</h2>
@if(config('legal.controller_name') || config('legal.brand'))@include('legal.operator')@else<p>{{ __('Der Betreiber dieser Instanz hat noch keine rechtlichen Angaben hinterlegt.') }}</p>@endif
<h2>{{ __('Hosting und Zugriffsdaten') }}</h2>
<p>{{ __('Beim Aufruf verarbeitet der Webserver technisch notwendige Daten wie IP-Adresse, Zeitpunkt, Seitenpfad, HTTP-Status und Browserinformationen. Dies dient der Auslieferung, Sicherheit und Fehleranalyse auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO.') }}</p>
@if(config('legal.hosting_location'))<p>{{ __('Hosting-Standort') }}: {{ __(config('legal.hosting_location')) }}.</p>@endif
@if(config('branding.hosting_name'))<p>{{ __('Gehostet bei') }} {{ config('branding.hosting_name') }}.</p>@endif
@if(config('legal.web_logs_days'))<p>{{ __('Aufbewahrung von Webserver-Zugriffslogs') }}: {{ config('legal.web_logs_days') }} {{ __('Tage') }}.</p>@endif
<h2>{{ __('Eingegebene URLs und Browser-Verlauf') }}</h2>
<p>{{ __('Prüf-URLs werden nur für die angeforderte Prüfung an den Server übertragen. Zielseiten und Bilder werden abgerufen und im Arbeitsspeicher verarbeitet. Es gibt keine serverseitige URL-Historie; Prüf-URLs und Seiteninhalte werden nicht in Datenbanken, Analytics, Application-Logs oder Sentry gespeichert. Der Zielseitenbetreiber kann den Abruf durch unseren Prüfserver in eigenen Logs erfassen. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO.') }}</p>
<p>{{ __('Der optionale URL-Verlauf speichert die letzten 20 gültigen Prüf-URLs ausschließlich im lokalen Browserspeicher dieses Geräts, bis du sie löschst oder die Browserdaten entfernst. Es erfolgt keine Synchronisierung mit unserem Server. Du kannst den Verlauf deaktivieren; dabei werden vorhandene Einträge gelöscht. Auf gemeinsam genutzten Geräten können andere Personen mit Zugriff auf dasselbe Browserprofil diese URLs sehen.') }}</p>
<p>{{ __('Teilbare Prüf-Links enthalten die Ziel-URL im URL-Fragment nach #. Dieses Fragment wird beim Seitenaufruf nicht an unseren Webserver übertragen. Beim Öffnen wird die Prüfung automatisch angefordert. Wer den Link erhält, kann die Ziel-URL lesen; beim Speichern als Lesezeichen oder im Browserverlauf kann sie im Browser gespeichert werden.') }}</p>
<h2>{{ __('Technisch notwendige Cookies und Einstellungen') }}</h2>
<p>{{ __('Sitzungs- und CSRF-Cookies sichern die Sprachwahl und Anfragen. Die Hell-/Dunkel-Auswahl, die Analyse-Auswahl und der optionale URL-Verlauf werden im Browser gespeichert. Der URL-Verlauf kann deaktiviert oder gelöscht werden. Technisch notwendige Speicherung erfolgt auf Grundlage von § 25 Abs. 2 Nr. 2 TDDDG und Art. 6 Abs. 1 lit. f DSGVO; der ausdrücklich gewünschte Verlauf dient der erneuten Prüfung.') }}</p>
<h2>{{ __('Matomo-Besucherstatistik') }}</h2>
@if(config('monitoring.matomo_enabled'))
<p>{{ __('Diese Instanz verwendet Matomo nach deiner Einwilligung. Ohne Zustimmung wird der Tracker nicht geladen. Query-Parameter, URL-Fragmente, Referrer und Prüf-URLs werden nicht übertragen. Tracking-Cookies und die Erkennung von Browsermerkmalen sind deaktiviert; Do Not Track wird respektiert. Rechtsgrundlagen sind Art. 6 Abs. 1 lit. a DSGVO und, soweit erforderlich, § 25 Abs. 1 TDDDG.') }}</p>
<p>{{ __('Deine Auswahl gilt höchstens 180 Tage. Du kannst sie unter „Analyse-Einstellungen“ jederzeit ändern. IP-Anonymisierung und Datenlöschung müssen durch den Betreiber in der Matomo-Instanz konfiguriert werden.') }}</p>
@if(config('legal.matomo_days'))<p>{{ __('Aufbewahrung der Matomo-Besuchsrohdaten') }}: {{ config('legal.matomo_days') }} {{ __('Tage') }}.</p>@endif
<p>{{ __('Zusammengefasste Matomo-Statistiken werden unbefristet gespeichert. Dies betrifft aggregierte Auswertungen, nicht die Besuchs-Rohdaten.') }}</p>
@else<p>{{ __('Die Besucheranalyse ist in dieser Installation deaktiviert. Es werden keine Besuche an Matomo gesendet.') }}</p>@endif
<h2>{{ __('Fehleranalyse mit Sentry') }}</h2>
@if(config('monitoring.enabled'))
<p>{{ __('Diese Instanz verwendet Sentry für bereinigte Fehlermeldungen, Logs und Anwendungsmetriken. Prüf-URLs, Request-Bodies, Cookies, Benutzerkennungen und IP-Adressen werden nicht in Ereignisse aufgenommen. Session Replay und Profiling sind deaktiviert. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO; das berechtigte Interesse ist die Fehlerbehebung und Zuverlässigkeit.') }}</p>
@if(config('legal.sentry_days'))<p>{{ __('Aufbewahrung der Sentry-Daten') }}: {{ config('legal.sentry_days') }} {{ __('Tage') }}.</p>@endif
<p><a href="{{ \App\Support\ExternalUrl::to('https://sentry.io/privacy/', 'privacy_sentry') }}" rel="noopener noreferrer" target="_blank">{{ __('Datenschutzhinweise von Sentry') }}</a></p>
@else<p>{{ __('Das externe Fehler-Monitoring ist in dieser Installation deaktiviert.') }}</p>@endif
<h2>{{ __('Administrator-Konten') }}</h2>
<p>{{ __('Für das Backend werden Name, E-Mail-Adresse, ein Passwort-Hash und gegebenenfalls verschlüsselte 2FA-Schlüssel und Wiederherstellungscodes sowie öffentliche Passkey-Daten gespeichert. Biometrische Daten verbleiben auf dem Authenticator. Instanz-Einstellungen und eigene Rechtstexte werden in der Datenbank gespeichert. Dies dient dem geschützten Betrieb auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO. Administrator-Daten bleiben bis zur Löschung des Kontos gespeichert.') }}</p>
<h2>{{ __('Deine Rechte') }}</h2>
<p>{{ __('Du hast nach Maßgabe der DSGVO Rechte auf Auskunft, Berichtigung, Löschung, Einschränkung und Datenübertragbarkeit. Du kannst Einwilligungen widerrufen, einer Verarbeitung auf Grundlage berechtigter Interessen widersprechen und dich bei einer zuständigen Datenschutzaufsichtsbehörde beschweren. Wende dich hierfür an den oben genannten Verantwortlichen.') }}</p>
@endif
@endsection
