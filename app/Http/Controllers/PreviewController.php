<?php

namespace App\Http\Controllers;

use App\Services\BestPracticeAnalyzer;
use App\Services\ProductSchemaAnalyzer;
use App\Services\TargetUrlPolicy;
use App\Services\TwitterCardAnalyzer;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\ResponseInterface;

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
            $oversized = false;
            try {
                $response = Http::withOptions($targetOptions + [
                    'allow_redirects' => false,
                    'decode_content' => false,
                    'on_headers' => function (ResponseInterface $response) use ($limit, &$oversized): void {
                        $length = $response->getHeaderLine('Content-Length');
                        if (ctype_digit($length) && (float) $length > $limit) {
                            $oversized = true;
                            throw new \RuntimeException(__('Die Antwort überschreitet die erlaubte Größe.'));
                        }
                    },
                    'progress' => function ($total, $downloaded) use ($limit, &$oversized): void {
                        if ($downloaded > $limit) {
                            $oversized = true;
                            throw new \RuntimeException(__('Die Antwort überschreitet die erlaubte Größe.'));
                        }
                    },
                ])->setHandler(new CurlHandler)->connectTimeout(5)->timeout(12)->withHeaders(['User-Agent' => 'OpenGraphStudio/1.0', 'Accept-Encoding' => 'identity'])->get($url);
            } catch (\Throwable $exception) {
                if ($oversized) {
                    throw new \RuntimeException(__('Die Antwort überschreitet die erlaubte Größe.'));
                }
                throw $exception;
            }
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
        foreach (['curl_init', 'dom_import_simplexml', 'dns_get_record'] as $function) {
            if (! function_exists($function)) {
                return response()->json(['message' => __('Die Prüfung ist auf diesem Server nicht verfügbar. Fehlende PHP-Funktion: ').$function, 'code' => 'server_configuration'], 503);
            }
        }
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
            if ($e instanceof ConnectionException || $e instanceof NetworkExceptionInterface) {
                $previous = $e instanceof ConnectionException ? $e->getPrevious() : $e;
                $errno = 0;
                if ($previous && method_exists($previous, 'getHandlerContext')) {
                    $errno = (int) ($previous->getHandlerContext()['errno'] ?? 0);
                } elseif ($previous && preg_match('/cURL error ([0-9]+):/', $previous->getMessage(), $match)) {
                    $errno = (int) $match[1];
                }
                $message = match ($errno) {
                    6 => __('Der Prüfserver konnte den Zielhost nicht auflösen. Bitte prüfe die URL und die DNS-Konfiguration des Servers.'),
                    7 => __('Der Prüfserver konnte keine Verbindung zur Zielseite herstellen. Bitte prüfe ausgehende Verbindungen und die Erreichbarkeit der Zielseite.'),
                    28 => __('Die Zielseite hat nicht rechtzeitig geantwortet. Bitte versuche es später erneut.'),
                    35, 51, 60, 77 => __('Die HTTPS-Verbindung zur Zielseite ist fehlgeschlagen. Bitte prüfe das Zertifikat der Zielseite und den CA-Zertifikatsspeicher des Prüfservers.'),
                    default => __('Der Prüfserver konnte die Zielseite nicht erreichen. Bitte prüfe die Serververbindung und die Erreichbarkeit der Zielseite.'),
                };
                Log::warning('Preview connection failed', ['curl_errno' => $errno]);

                return response()->json(['message' => $message, 'code' => 'connection_failed'], 422);
            }
            if ($e::class === \RuntimeException::class) {
                return response()->json(['message' => $e->getMessage(), 'code' => 'target_rejected'], 422);
            }
            Log::error('Preview processing failed', ['exception_type' => $e::class]);

            return response()->json(['message' => __('Die Prüfung ist wegen eines internen Serverfehlers fehlgeschlagen. Bitte kontaktiere den Betreiber.'), 'code' => 'processing_failed'], 500);
        }
    }
}
