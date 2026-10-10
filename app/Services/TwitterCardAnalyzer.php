<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;

class TwitterCardAnalyzer
{
    public function analyze(array $tags, string $url, callable $fetchImage): array
    {
        $first = fn (string $key): string => $tags[$key][0] ?? '';
        $checks = [];
        $add = function (string $label, string $status, string $detail) use (&$checks): void {
            $checks[] = compact('label', 'status', 'detail');
        };
        $declared = $first('twitter:card');
        $card = $declared;
        $inferred = $card === '' && $first('og:type') && $first('og:title') && $first('og:description');
        if ($inferred) {
            $card = 'summary';
        }
        $valid = in_array($card, ['summary', 'summary_large_image', 'player', 'app'], true);
        $add('twitter:card', $declared === '' ? 'warn' : ($valid ? 'pass' : 'fail'), $declared ?: ($inferred ? __('Fehlt; mögliche Summary-Karte aus og:type, og:title und og:description simuliert.') : __('Fehlt. Kartentyp explizit angeben; keine Karte ableitbar.')));
        $sources = [];
        $values = [];
        foreach (['title', 'description', 'image', 'image:alt'] as $field) {
            $native = 'twitter:'.$field;
            $fallback = 'og:'.$field;
            $sources[$field] = $first($native) !== '' ? $native : ($first($fallback) !== '' ? $fallback : null);
            $values[$field] = $sources[$field] ? $first($sources[$field]) : '';
            $status = $sources[$field] === $native ? 'pass' : ($sources[$field] ? 'warn' : (in_array($field, ['title', 'image'], true) && $card !== 'app' ? 'fail' : 'warn'));
            $add($native, $status, $sources[$field] ? ($sources[$field] === $native ? $values[$field] : __('Open-Graph-Fallback aus ').$fallback.': '.$values[$field]) : __('Keine Angabe und kein Open-Graph-Fallback vorhanden.'));
        }
        foreach (['title' => 70, 'description' => 200, 'image:alt' => 420] as $field => $limit) {
            if ($values[$field] !== '') {
                $length = mb_strlen($values[$field]);
                $add(__('Länge ').$field, $length <= $limit ? 'pass' : 'warn', $length.__(' Zeichen; Richtwert aus der Cards-Spezifikation: maximal ').$limit.'.');
            }
        }
        foreach (['site', 'creator'] as $field) {
            $handle = $first('twitter:'.$field);
            $id = $first('twitter:'.$field.':id');
            if ($handle !== '') {
                $add('twitter:'.$field, preg_match('/^@[A-Za-z0-9_]{1,15}$/D', $handle) ? 'pass' : 'fail', $handle.__(' · Erwartet wird ein @Benutzername.'));
            } elseif ($id !== '') {
                $add('twitter:'.$field.':id', ctype_digit($id) ? 'pass' : 'fail', $id.__(' · Erwartet wird eine numerische Account-ID.'));
            } else {
                $add('twitter:'.$field, 'warn', __('Optionale Account-Zuordnung fehlt.'));
            }
        }
        if ($card === 'player') {
            foreach (['player', 'player:width', 'player:height'] as $field) {
                $value = $first('twitter:'.$field);
                $ok = $field === 'player'
                    ? filter_var($value, FILTER_VALIDATE_URL) && strtolower(parse_url($value, PHP_URL_SCHEME) ?? '') === 'https'
                    : ctype_digit($value) && (int) $value > 0;
                $add('twitter:'.$field, $ok ? 'pass' : 'fail', $value ?: __('Für Player-Karten erforderlich.'));
            }
            $add(__('Player-Vorschau'), 'warn', __('Nur statische Vorschau. Player wird nicht ausgeführt; Wiedergabe und Freigabe durch X werden nicht geprüft.'));
        }
        if ($card === 'app') {
            $appPresent = false;
            foreach (['iphone', 'ipad', 'googleplay'] as $platform) {
                $id = $first('twitter:app:id:'.$platform);
                if ($id !== '') {
                    $appPresent = true;
                    $add('twitter:app:id:'.$platform, 'pass', $id);
                    $add('twitter:app:name:'.$platform, $first('twitter:app:name:'.$platform) ? 'pass' : 'warn', $first('twitter:app:name:'.$platform) ?: __('App-Name fehlt.'));
                }
            }
            $add(__('App-Karte'), $appPresent ? 'warn' : 'fail', $appPresent ? __('Statische Simulation; Store-Daten und Installationsverhalten werden nicht geprüft.') : __('Mindestens eine twitter:app:id:iphone, :ipad oder :googleplay angeben.'));
        }
        $image = null;
        if ($values['image'] !== '') {
            $absolute = filter_var($values['image'], FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($values['image'], PHP_URL_SCHEME) ?? ''), ['http', 'https']);
            $add(__('Twitter-Bild-URL'), $absolute ? 'pass' : 'warn', $absolute ? $values['image'] : __('Relative URL wird lokal aufgelöst; für X eine absolute URL verwenden.'));
            try {
                [$bytes] = $fetchImage($values['image']);
                $size = @getimagesizefromstring($bytes);
                if (! $size || ! in_array($size['mime'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                    throw new \RuntimeException(__('Kein unterstütztes Bild (JPEG, PNG, GIF oder WebP).'));
                }
                $image = 'data:'.$size['mime'].';base64,'.base64_encode($bytes);
                $add(__('Twitter-Bild erreichbar'), 'pass', $size[0].' × '.$size[1].__(' Pixel · ').$size['mime']);
                $add(__('Twitter-Bildgröße'), strlen($bytes) < 5 * 1024 * 1024 ? 'pass' : 'fail', __('Dateigröße: ').round(strlen($bytes) / 1024).__(' KiB; Cards-Richtwert: unter 5 MiB.'));
                if (in_array($card, ['summary', 'summary_large_image'], true)) {
                    $large = $card === 'summary_large_image';
                    $minimum = $large ? [300, 157] : [144, 144];
                    $ok = $size[0] >= $minimum[0] && $size[1] >= $minimum[1] && $size[0] <= 4096 && $size[1] <= 4096;
                    $add(__('Twitter-Bildmaße'), $ok ? 'pass' : 'fail', __('Cards-Richtwert: mindestens ').$minimum[0].' × '.$minimum[1].__(', maximal 4096 × 4096 Pixel.'));
                    $ratio = $large ? 2 : 1;
                    $add(__('Twitter-Bildzuschnitt'), abs($size[0] / $size[1] - $ratio) < .1 ? 'pass' : 'warn', $large ? __('Große Bildkarte: ungefähr 2:1. Vorschau zeigt den möglichen Zuschnitt.') : __('Summary-Karte: quadratisches Vorschaubild. Vorschau zeigt den möglichen Zuschnitt.'));
                }
            } catch (\Throwable $e) {
                $add(__('Twitter-Bild erreichbar'), 'fail', $e instanceof \RuntimeException && ! $e instanceof ConnectionException ? $e->getMessage() : __('Twitter-Bild konnte nicht geladen werden.'));
            }
        }
        foreach ($tags as $key => $entries) {
            if (str_starts_with($key, 'twitter:') && count($entries) > 1) {
                $add(__('Mehrfaches ').$key, 'warn', __('Mehrere Werte gefunden; diese Simulation verwendet den ersten.'));
            }
        }

        return [
            'card' => $card, 'declared_card' => $declared, 'inferred' => (bool) $inferred,
            'previewable' => $valid, 'title' => $values['title'], 'description' => $values['description'],
            'image' => $image, 'alt' => $values['image:alt'], 'site' => $first('twitter:site'),
            'creator' => $first('twitter:creator'), 'sources' => $sources, 'checks' => $checks,
            'domain' => parse_url($url, PHP_URL_HOST),
        ];
    }
}
