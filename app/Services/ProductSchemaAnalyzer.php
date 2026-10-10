<?php

namespace App\Services;

use DOMXPath;

class ProductSchemaAnalyzer
{
    public function analyze(DOMXPath $xpath, string $pageUrl, callable $fetchImage): array
    {
        $checks = [];
        $products = [];
        $add = function (string $label, string $status, string $detail) use (&$checks): void {
            $checks[] = compact('label', 'status', 'detail');
        };
        $walk = function ($node, $context = null) use (&$walk, &$products): void {
            if (! is_array($node)) {
                return;
            }
            if (array_is_list($node)) {
                foreach ($node as $child) {
                    $walk($child, $context);
                }

                return;
            }
            $context = $node['@context'] ?? $context;
            $contexts = is_array($context) ? $context : [$context];
            $schemaContext = in_array('https://schema.org', $contexts, true) || in_array('http://schema.org', $contexts, true) || in_array('https://schema.org/', $contexts, true) || in_array('http://schema.org/', $contexts, true);
            $types = (array) ($node['@type'] ?? []);
            if (($schemaContext && in_array('Product', $types, true)) || in_array('https://schema.org/Product', $types, true) || in_array('http://schema.org/Product', $types, true)) {
                $products[] = $node;
            }
            foreach ($node as $key => $child) {
                if ($key !== '@context' && is_array($child)) {
                    $walk($child, $context);
                }
            }
        };
        foreach ($xpath->query('//script[translate(normalize-space(@type), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="application/ld+json"]') as $index => $script) {
            try {
                $data = json_decode($script->textContent, true, 64, JSON_THROW_ON_ERROR);
                if (! is_array($data)) {
                    throw new \JsonException;
                }
                $add('JSON-LD #'.($index + 1), 'pass', __('Gültiges JSON-LD-JSON gelesen.'));
                $walk($data);
            } catch (\JsonException $e) {
                $add('JSON-LD #'.($index + 1), 'fail', __('Ungültiges JSON im JSON-LD-Block. Syntax und Verschachtelung prüfen.'));
            }
        }
        $add('Schema.org Product', $products ? 'pass' : 'warn', $products ? count($products).' '.__('Produktdatensätze erkannt.') : __('Keine Schema.org-Produktdaten erkannt. Für allgemeine Websites ist Product nicht erforderlich.'));
        $preview = null;
        foreach (array_slice($products, 0, 10) as $index => $product) {
            $prefix = 'Product #'.($index + 1).' · ';
            $name = is_string($product['name'] ?? null) ? trim($product['name']) : '';
            $description = is_string($product['description'] ?? null) ? trim($product['description']) : '';
            $url = is_string($product['url'] ?? null) ? trim($product['url']) : '';
            $imageValue = $product['image'] ?? null;
            if (is_array($imageValue) && array_is_list($imageValue)) {
                $imageValue = $imageValue[0] ?? null;
            }
            if (is_array($imageValue)) {
                $imageValue = $imageValue['url'] ?? $imageValue['contentUrl'] ?? null;
            }
            $imageUrl = is_string($imageValue) ? trim($imageValue) : '';
            $add($prefix.'name', $name !== '' ? 'pass' : 'fail', $name ?: __('Produktname fehlt oder ist kein Text.'));
            $add($prefix.'description', $description !== '' ? 'pass' : 'warn', $description ?: __('Produktbeschreibung fehlt oder ist kein Text.'));
            $validUrl = fn (string $value): bool => filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), ['http', 'https']) && ! parse_url($value, PHP_URL_USER) && ! parse_url($value, PHP_URL_PASS);
            $add($prefix.'url', $validUrl($url) ? 'pass' : 'warn', $validUrl($url) ? $url : __('Eine vollständige öffentliche HTTP-/HTTPS-Produkt-URL angeben.'));
            $image = null;
            if ($imageUrl === '') {
                $add($prefix.'image', 'warn', __('Produktbild fehlt oder hat kein unterstütztes URL-/ImageObject-Format.'));
            } else {
                $add($prefix.'image', $validUrl($imageUrl) ? 'pass' : 'warn', $validUrl($imageUrl) ? $imageUrl : __('Relative Produktbild-URL wird lokal aufgelöst; absolute URL bevorzugen.'));
                if ($index === 0) {
                    try {
                        [$bytes] = $fetchImage($imageUrl);
                        $size = @getimagesizefromstring($bytes);
                        if (! $size || ! in_array($size['mime'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                            throw new \RuntimeException;
                        }
                        $image = 'data:'.$size['mime'].';base64,'.base64_encode($bytes);
                        $add($prefix.__('Bild erreichbar'), 'pass', $size[0].' × '.$size[1].' '.__('Pixel'));
                    } catch (\Throwable $e) {
                        $add($prefix.__('Bild erreichbar'), 'fail', __('Produktbild konnte nicht als unterstütztes Rasterbild geladen werden.'));
                    }
                }
            }
            if ($index === 0 && $name !== '') {
                $preview = ['title' => $name, 'description' => $description, 'image' => $image, 'url' => $validUrl($url) ? $url : $pageUrl];
            }
        }
        if (count($products) > 1) {
            $add(__('Produktauswahl'), 'warn', __('Mehrere Produkte gefunden; die Teams-Simulation verwendet das erste. Teams kann anders auswählen.'));
        }
        if (count($products) > 10) {
            $add(__('Prüfumfang'), 'warn', __('Maximal zehn Produktdatensätze werden detailliert geprüft.'));
        }
        $add(__('Schema-Prüfumfang'), 'warn', __('Geprüft werden JSON-LD-Syntax und grundlegende Produktfelder für die Teams-Vorschau. Keine vollständige Schema.org-, Google-Rich-Results- oder Teams-Freigabeprüfung. Microdata, RDFa und externe JSON-LD-Kontexte werden nicht ausgewertet.'));

        return ['products' => array_slice($products, 0, 10), 'checks' => $checks, 'preview' => $preview];
    }
}
