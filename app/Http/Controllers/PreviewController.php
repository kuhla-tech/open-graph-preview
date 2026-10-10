<?php

namespace App\Http\Controllers;

use App\Services\BestPracticeAnalyzer;
use App\Services\ProductSchemaAnalyzer;
use App\Services\TargetUrlPolicy;
use App\Services\TwitterCardAnalyzer;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PreviewController extends Controller
{
    private function valid(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https']) && ! parse_url($url, PHP_URL_USER) && ! parse_url($url, PHP_URL_PASS);
    }

    private function fetch(string $url, int $limit): array
    {
        for ($i = 0; $i < 6; $i++) {
            if (! $this->valid($url)) {
                throw new \RuntimeException(__('Nur vollständige HTTP- und HTTPS-URLs ohne Zugangsdaten sind erlaubt.'));
            }
            $targetOptions = (new TargetUrlPolicy)->options($url);
            $response = Http::withOptions($targetOptions + ['allow_redirects' => false, 'stream' => true])->connectTimeout(5)->timeout(12)->withHeaders(['User-Agent' => 'LocalOpenGraphPreview/1.0'])->get($url);
            if ($response->redirect()) {
                $url = (string) UriResolver::resolve(new Uri($url), new Uri($response->header('Location')));

                continue;
            }
            if (! $response->successful()) {
                throw new \RuntimeException(__('Die Adresse antwortet mit HTTP ').$response->status().'.');
            }
            $stream = $response->toPsrResponse()->getBody();
            $body = '';
            while (! $stream->eof() && strlen($body) <= $limit) {
                $body .= $stream->read(8192);
            }
            $stream->close();
            if (strlen($body) > $limit) {
                throw new \RuntimeException(__('Die Antwort überschreitet die erlaubte Größe.'));
            }

            return [$body, $url, $response->header('Content-Type')];
        }
        throw new \RuntimeException(__('Zu viele Weiterleitungen.'));
    }

    public function __invoke(Request $request)
    {
        $input = $request->validate(['url' => ['required', 'string', 'max:2048']], ['url.required' => __('Bitte gib eine URL ein.'), 'url.string' => __('Die URL muss ein Text sein.'), 'url.max' => __('Die URL darf höchstens 2048 Zeichen enthalten.')]);
        try {
            [$html, $url, $mime] = $this->fetch(trim($input['url']), 2097152);
            if (! str_contains(strtolower($mime), 'html')) {
                throw new \RuntimeException(__('Die Adresse liefert keine HTML-Seite.'));
            }
            $doc = new \DOMDocument;
            $previous = libxml_use_internal_errors(true);
            $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $xpath = new \DOMXPath($doc);
            $tags = [];
            foreach ($xpath->query('//head/meta') as $meta) {
                $key = strtolower(trim($meta->getAttribute('property') ?: $meta->getAttribute('name')));
                if (str_starts_with($key, 'og:') || str_starts_with($key, 'twitter:') || in_array($key, ['description', 'theme-color', 'robots'])) {
                    $tags[$key][] = trim($meta->getAttribute('content'));
                }
            }
            $first = fn ($key) => $tags[$key][0] ?? '';
            $checks = [];
            $add = function ($label, $status, $detail) use (&$checks) {
                $checks[] = compact('label', 'status', 'detail');
            };
            foreach (['og:title', 'og:type', 'og:image', 'og:url'] as $key) {
                $add($key, $first($key) !== '' ? 'pass' : 'fail', $first($key) ?: __('Pflichtfeld fehlt oder ist leer.'));
            }
            foreach (['og:description', 'og:site_name', 'og:image:alt'] as $key) {
                $add($key, $first($key) !== '' ? 'pass' : 'warn', $first($key) ?: __('Empfohlenes Feld fehlt.'));
            }
            if ($first('og:url')) {
                $add(__('Kanonische URL'), $this->valid($first('og:url')) ? 'pass' : 'fail', __('og:url sollte eine vollständige HTTP- oder HTTPS-URL sein.'));
            }
            $imageResponses = [];
            $fetchImage = function (string $imageUrl) use ($url, &$imageResponses): array {
                $resolved = (string) UriResolver::resolve(new Uri($url), new Uri($imageUrl));

                return $imageResponses[$resolved] ??= $this->fetch($resolved, 8388608);
            };
            $image = null;
            $imageInfo = null;
            $linkedInChecks = [];
            $imageUrl = $first('og:image') ?: $first('og:image:url');
            if ($imageUrl) {
                $add(__('Bild-URL'), $this->valid($imageUrl) ? 'pass' : 'warn', __('Für zuverlässiges Teilen eine absolute Bild-URL verwenden.'));
                try {
                    [$bytes] = $fetchImage($imageUrl);
                    $size = @getimagesizefromstring($bytes);
                    if (! $size || ! in_array($size['mime'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                        throw new \RuntimeException(__('Kein unterstütztes Rasterbild (JPEG, PNG, GIF oder WebP).'));
                    }
                    $linkedInChecks[] = ['label' => __('LinkedIn-Bildmaße'), 'status' => $size[0] >= 1200 && $size[1] >= 627 ? 'pass' : 'warn', 'detail' => __('Bild: ').$size[0].' × '.$size[1].__(' Pixel. LinkedIn nennt mindestens 1200 × 627 Pixel für das Sharing-Modul.')];
                    $linkedInChecks[] = ['label' => __('LinkedIn-Bildzuschnitt'), 'status' => abs($size[0] / $size[1] - 1.91) < .1 ? 'pass' : 'warn', 'detail' => __('Zielverhältnis 1,91:1. Die große Vorschau simuliert einen möglichen Zuschnitt.')];
                    $linkedInChecks[] = ['label' => __('LinkedIn-Dateigröße'), 'status' => strlen($bytes) <= 5000000 ? 'pass' : 'fail', 'detail' => __('Dateigröße: ').round(strlen($bytes) / 1024).__(' KiB. LinkedIn nennt maximal 5 MB.')];
                    $imageInfo = ['bytes' => strlen($bytes), 'mime' => $size['mime']];
                    $image = 'data:'.$size['mime'].';base64,'.base64_encode($bytes);
                    $add(__('Bild erreichbar'), 'pass', $size[0].' × '.$size[1].__(' Pixel · ').$size['mime']);
                    $add(__('Bildformat'), abs($size[0] / $size[1] - 1.91) < .15 && $size[0] >= 1200 ? 'pass' : 'warn', __('Gestaltungsempfehlung: 1200 × 630 Pixel. Kein OG-Pflichtmaß.'));
                    foreach (['width' => 0, 'height' => 1] as $dimension => $index) {
                        if ($first('og:image:'.$dimension)) {
                            $add('og:image:'.$dimension, (string) $size[$index] === $first('og:image:'.$dimension) ? 'pass' : 'fail', __('Tatsächlicher Wert: ').$size[$index].__(' Pixel.'));
                        }
                    }
                } catch (\Throwable $e) {
                    $add(__('Bild erreichbar'), 'fail', $e instanceof \RuntimeException && ! $e instanceof ConnectionException ? $e->getMessage() : __('Bild konnte nicht geladen werden.'));
                }
            }
            foreach (['og:title', 'og:description', 'og:url', 'og:type'] as $key) {
                if (count($tags[$key] ?? []) > 1) {
                    $add(__('Mehrfaches ').$key, 'warn', __('Mehrere Werte vorhanden; die Vorschau verwendet den ersten.'));
                }
            }

            $canonical = trim($xpath->evaluate('string(//head/link[contains(concat(" ", normalize-space(translate(@rel, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")), " "), " canonical ")]/@href)'));
            if ($canonical !== '') {
                $canonical = (string) UriResolver::resolve(new Uri($url), new Uri($canonical));
            }
            $bestPractices = (new BestPracticeAnalyzer)->analyze($tags, $url, $canonical, $imageInfo);
            $checks = array_merge($checks, $bestPractices);
            $twitter = (new TwitterCardAnalyzer)->analyze($tags, $url, $fetchImage);
            $twitter['checks'] = array_merge($twitter['checks'], $bestPractices);

            $schema = (new ProductSchemaAnalyzer)->analyze($xpath, $url, $fetchImage);

            return response()->json(['schema' => $schema, 'linkedin_checks' => array_merge($checks, $linkedInChecks, [['label' => __('Großes LinkedIn-Linkbild'), 'status' => 'warn', 'detail' => __('og:image liefert das Linkbild. Ob LinkedIn eine große Karte oder ein kleines Thumbnail zeigt, entscheidet die Plattform; es gibt keinen entsprechenden Open-Graph-Schalter.')]]), 'twitter' => $twitter, 'url' => $url, 'title' => $first('og:title') ?: trim($xpath->evaluate('string(//head/title)')) ?: __('Kein Titel'), 'description' => $first('og:description') ?: $first('description'), 'site' => $first('og:site_name') ?: parse_url($url, PHP_URL_HOST), 'image' => $image, 'checks' => $checks, 'tags' => $tags, 'fallback' => ! $first('og:title') || ! $first('og:description')]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e instanceof \RuntimeException && ! $e instanceof ConnectionException ? $e->getMessage() : __('Die Seite konnte nicht geladen werden. Prüfe URL und lokalen Server.')], 422);
        }
    }
}
