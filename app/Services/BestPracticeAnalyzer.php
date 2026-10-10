<?php

namespace App\Services;

class BestPracticeAnalyzer
{
    public function analyze(array $tags, string $url, string $canonical, ?array $imageInfo): array
    {
        $first = fn (string $key): string => $tags[$key][0] ?? '';
        $checks = [];
        $warn = function (string $label, string $detail) use (&$checks): void {
            $checks[] = ['label' => $label, 'status' => 'warn', 'detail' => $detail, 'category' => 'best_practice'];
        };
        foreach (['og:title' => 60, 'og:description' => 160] as $key => $limit) {
            if (mb_strlen($first($key)) > $limit) {
                $warn(__('Textlänge ').$key, mb_strlen($first($key)).__(' Zeichen. Gestaltungsempfehlung: etwa ').$limit.__(' oder weniger, damit die Vorschau seltener gekürzt wird. Dies ist keine Open-Graph-Grenze.'));
            }
        }
        foreach (['og:title', 'og:description', 'twitter:title', 'twitter:description'] as $key) {
            $value = $first($key);
            if ($value !== '' && (strip_tags($value) !== $value || str_contains($value, '
'))) {
                $warn(__('Klartext ').$key, __('HTML-Markup oder Zeilenumbrüche gefunden. Für konsistente Linkkarten einen gut lesbaren Klartext verwenden.'));
            }
        }
        if (strtolower(parse_url($url, PHP_URL_SCHEME) ?? '') === 'http') {
            $warn(__('HTTPS für die Website'), __('Für öffentlich geteilte Links HTTPS verwenden. HTTP kann für lokale Tests beabsichtigt sein.'));
        }
        foreach (['og:url', 'og:image', 'twitter:image'] as $key) {
            $value = $first($key);
            if ($value !== '' && strtolower(parse_url($value, PHP_URL_SCHEME) ?? '') === 'http') {
                $warn(__('HTTPS für ').$key, __('Für öffentliche URLs und Bilder HTTPS bevorzugen, um gemischte Inhalte und Einschränkungen beim Abruf zu vermeiden.'));
            }
        }
        $ogUrl = $first('og:url');
        if ($canonical === '') {
            $warn(__('Canonical-Link'), __('Ein link rel="canonical" hilft, die bevorzugte Seiten-URL eindeutig anzugeben. Mit og:url abstimmen.'));
        } elseif ($ogUrl !== '' && rtrim($canonical, '/') !== rtrim($ogUrl, '/')) {
            $warn(__('Canonical und og:url'), __('Canonical-Link und og:url unterscheiden sich. Prüfen, ob beide dieselbe bevorzugte Seite identifizieren sollen: ').$canonical);
        }
        if ($ogUrl !== '' && rtrim($ogUrl, '/') !== rtrim($url, '/')) {
            $warn(__('Seiten-URL und og:url'), __('og:url unterscheidet sich von der abgerufenen URL. Bei URL-Varianten kann das beabsichtigt sein; versehentliche Verweise auf die Startseite vermeiden.'));
        }
        $locale = $first('og:locale');
        if ($locale === '') {
            $warn(__('Sprache der Vorschau'), __('og:locale explizit setzen, z. B. de_DE. Der Open-Graph-Standard verwendet sonst en_US.'));
        } elseif (! preg_match('/^[a-z]{2}_[A-Z]{2}$/D', $locale)) {
            $warn(__('Format og:locale'), __('Übliches Format language_TERRITORY verwenden, z. B. de_DE statt de-DE.'));
        }
        if ($first('og:image') !== '' || $first('og:image:url') !== '') {
            foreach (['width', 'height', 'type'] as $field) {
                if ($first('og:image:'.$field) === '') {
                    $warn(__('Bildmetadaten ').$field, 'og:image:'.$field.__(' ergänzen. Explizite Bildmetadaten erleichtern die Auswertung der Vorschau.'));
                }
            }
        }
        if ($imageInfo !== null) {
            if ($imageInfo['bytes'] > 1000000) {
                $warn(__('Bild optimieren'), round($imageInfo['bytes'] / 1024).__(' KiB. Gestaltungsempfehlung: möglichst unter 1 MB komprimieren, ohne sichtbaren Qualitätsverlust. Plattformlimits gelten zusätzlich.'));
            }
            $declaredType = $first('og:image:type');
            if ($declaredType !== '' && strtolower($declaredType) !== $imageInfo['mime']) {
                $warn(__('Bild-MIME-Typ'), __('og:image:type stimmt nicht mit dem geladenen Bild überein. Tatsächlich: ').$imageInfo['mime'].'.');
            }
        }
        $robots = strtolower($first('robots'));
        if (preg_match('/\b(noindex|none)\b/', $robots)) {
            $warn(__('Indexierung'), __('Die Seite enthält robots=noindex/none. Falls öffentliche Auffindbarkeit gewünscht ist, Einstellung prüfen. Das ist kein Nachweis, dass Social-Vorschauen blockiert werden.'));
        }

        return $checks;
    }
}
