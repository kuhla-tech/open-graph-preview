<?php

namespace Tests\Feature;

use App\Support\ExternalUrl;
use Tests\TestCase;

class ExternalLinksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['site.utm_source' => 'opengraph.works']);
    }

    public function test_outbound_urls_preserve_existing_parameters_and_fragments(): void
    {
        $url = ExternalUrl::to('https://example.com/path?token=a%20b&key=1&key=2&utm_source=old#section', 'footer_project');
        $this->assertStringContainsString('token=a%20b&key=1&key=2', $url);
        $this->assertStringEndsWith('#section', $url);
        $this->assertSame(1, substr_count($url, 'utm_source='));
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('opengraph.works', $query['utm_source']);
        $this->assertSame('referral', $query['utm_medium']);
        $this->assertSame('outbound_links', $query['utm_campaign']);
        $this->assertSame('footer_project', $query['utm_content']);
        $this->assertSame('mailto:kontakt@opengraph.works', ExternalUrl::to('mailto:kontakt@opengraph.works', 'email'));
        $this->assertSame(config('app.url').'/de/', ExternalUrl::to(config('app.url').'/de/', 'home'));
    }

    public function test_all_rendered_external_navigation_links_have_utm_parameters(): void
    {
        config(['branding.owner_name' => 'kuhla.tech', 'branding.owner_url' => 'https://kuhla.tech', 'branding.hosting_name' => 'fibranode', 'branding.hosting_url' => 'https://fibranode.com']);
        foreach (['de', 'en'] as $locale) {
            foreach (['/', '/impressum', '/datenschutz'] as $path) {
                $response = $this->get('/'.$locale.$path)->assertOk();
                preg_match_all('/<a[^>]+href="(https?:[^" ]+)"/i', $response->getContent(), $matches);
                foreach ($matches[1] as $href) {
                    $href = html_entity_decode($href);
                    if (parse_url($href, PHP_URL_HOST) === parse_url(config('app.url'), PHP_URL_HOST)) {
                        continue;
                    }
                    parse_str(parse_url($href, PHP_URL_QUERY) ?? '', $query);
                    $this->assertSame('opengraph.works', $query['utm_source'] ?? null, $href);
                    $this->assertSame('referral', $query['utm_medium'] ?? null);
                    $this->assertSame('outbound_links', $query['utm_campaign'] ?? null);
                    $this->assertNotEmpty($query['utm_content'] ?? null);
                }
                if ($path === '/') {
                    $response->assertSee('https://fibranode.com?utm_source=opengraph.works', false);
                }
            }
        }
    }

    public function test_footer_credits_are_configurable_translated_and_optional(): void
    {
        config(['app.name' => 'My Studio', 'branding.owner_name' => 'Example <Team>', 'branding.owner_url' => 'https://example.org', 'branding.hosting_name' => 'My Host', 'branding.hosting_url' => 'javascript:alert(1)']);
        $this->get('/de/')->assertOk()->assertSee('My Studio ist ein Projekt von')->assertSee('Example &lt;Team&gt;', false)->assertSee('https://example.org?utm_source=', false)->assertSee('My Host')->assertDontSee('javascript:alert(1)', false);
        $this->get('/en/')->assertOk()->assertSee('My Studio is a project by')->assertSee('Hosted by');
        config(['branding.owner_name' => '', 'branding.hosting_name' => '']);
        $this->get('/de/')->assertOk()->assertDontSee('<span class="footer-credit">', false);
    }
}
