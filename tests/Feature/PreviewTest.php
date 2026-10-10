<?php

namespace Tests\Feature;

use App\Services\TargetUrlPolicy;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PreviewTest extends TestCase
{
    public function test_hosted_transport_uses_supported_proxy_options_and_aborts_oversized_downloads(): void
    {
        config(['monitoring.allow_private_targets' => false]);
        $options = app(TargetUrlPolicy::class)->options('https://8.8.8.8/');
        $this->assertSame('', $options['proxy']);
        $this->assertSame(['8.8.8.8:443:8.8.8.8'], $options['curl'][CURLOPT_RESOLVE]);
        $this->assertArrayNotHasKey(CURLOPT_PROXY, $options['curl']);
        $this->assertArrayNotHasKey(CURLOPT_NOPROXY, $options['curl']);
        $captured = [];
        Http::fake(function ($request, $options) use (&$captured) {
            $captured = $options;
            $options['progress'](0, 2097153);
        });
        $this->postJson('/preview', ['url' => 'https://8.8.8.8/'])->assertStatus(422)->assertJsonPath('message', 'Die Antwort überschreitet die erlaubte Größe.');
        $this->assertEmpty($captured['stream'] ?? false);
        $this->assertFalse($captured['decode_content']);
    }

    public function test_connection_failures_are_specific_and_do_not_expose_target_urls(): void
    {
        $request = new Request('GET', 'https://demo.test/private?secret=value');
        Http::fake(fn () => throw new ConnectionException(
            'private request detail', 0, new ConnectException('cURL error 60: private request detail', $request)
        ));
        $response = $this->postJson('/preview', ['url' => 'https://demo.test/private?secret=value']);
        $response->assertStatus(422)->assertJsonPath('code', 'connection_failed');
        $this->assertStringContainsString('CA-Zertifikatsspeicher', $response->json('message'));
        $response->assertDontSee('secret')->assertDontSee('demo.test')->assertDontSee('private request detail');
    }

    public function test_unexpected_errors_are_not_misreported_as_target_connection_failures(): void
    {
        Http::fake(fn () => throw new \Error('sensitive internal diagnostic'));
        $this->postJson('/preview', ['url' => 'https://demo.test/page'])->assertStatus(500)->assertJsonPath('code', 'processing_failed')->assertDontSee('sensitive internal diagnostic');
    }

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
