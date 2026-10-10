<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PreviewTest extends TestCase
{
    public function test_metadata_and_local_image_are_analyzed(): void
    {
        Http::fake([
            'https://demo.test/page' => Http::response('<html><head><title>Fallback</title><meta property="og:title" content="Grüße &amp; Test"><meta property="og:type" content="website"><meta property="og:url" content="https://demo.test/page"><meta property="og:image" content="/image.png"><meta property="og:image:width" content="99"></head></html>', 200, ['Content-Type' => 'text/html']),
            'https://demo.test/image.png' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='), 200, ['Content-Type' => 'image/png']),
        ]);
        $this->postJson('/preview', ['url' => 'https://demo.test/page'])->assertOk()->assertJsonPath('title', 'Grüße & Test')->assertJsonPath('checks.0.status', 'pass')->assertJsonPath('checks.4.status', 'warn')->assertJsonPath('checks.11.status', 'fail');
    }

    public function test_missing_tags_do_not_become_valid_through_fallback(): void
    {
        Http::fake(['*' => Http::response('<html><head><title>Fallback</title></head></html>', 200, ['Content-Type' => 'text/html'])]);
        $this->postJson('/preview', ['url' => 'http://localhost/page'])->assertOk()->assertJsonPath('title', 'Fallback')->assertJsonPath('checks.0.status', 'fail')->assertJsonPath('fallback', true);
    }

    public function test_non_http_urls_are_rejected(): void
    {
        Http::fake();
        $this->postJson('/preview', ['url' => 'file:///etc/passwd'])->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_redirects_are_followed_and_failures_reported(): void
    {
        Http::fake([
            'https://demo.test/start' => Http::response('', 302, ['Location' => '/end']),
            'https://demo.test/end' => Http::response('missing', 404),
        ]);
        $this->postJson('/preview', ['url' => 'https://demo.test/start'])->assertStatus(422)->assertJsonPath('message', 'Die Adresse antwortet mit HTTP 404.');
    }
}
