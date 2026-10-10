<?php

namespace Tests\Feature;

use Tests\TestCase;

class SearchDiscoveryTest extends TestCase
{
    public function test_verification_tags_are_optional_and_escaped(): void
    {
        config(['search.google_verification' => null, 'search.bing_verification' => null]);
        $this->get('/de/')->assertOk()->assertDontSee('google-site-verification')->assertDontSee('msvalidate.01');
        config(['search.google_verification' => 'google-token', 'search.bing_verification' => 'bing"token']);
        foreach (['/de/', '/en/impressum', '/de/datenschutz'] as $path) {
            $this->get($path)->assertOk()->assertSee('name="google-site-verification" content="google-token"', false)->assertSee('name="msvalidate.01" content="bing&quot;token"', false);
        }
    }

    public function test_sitemap_contains_only_public_pages_and_language_alternates(): void
    {
        config(['app.url' => 'https://opengraph.works']);
        $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('<?xml-stylesheet type="text/css" href="/sitemap.css"?>', false);
        $xml = simplexml_load_string($response->getContent());
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->registerXPathNamespace('h', 'http://www.w3.org/1999/xhtml');
        $this->assertCount(6, $xml->xpath('//s:url'));
        $this->assertCount(12, $xml->xpath('//h:link'));
        foreach (['/', '/impressum', '/datenschutz'] as $path) {
            $this->assertStringContainsString('<loc>https://opengraph.works/de'.$path.'</loc>', $response->getContent());
            $this->assertStringContainsString('<loc>https://opengraph.works/en'.$path.'</loc>', $response->getContent());
        }
        $response->assertDontSee('/preview')->assertDontSee('og.test');
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://opengraph.works/sitemap.xml', false);
    }
}
