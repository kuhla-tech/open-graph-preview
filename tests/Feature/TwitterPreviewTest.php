<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwitterPreviewTest extends TestCase
{
    private function page(string $metas): void
    {
        Http::swap(new Factory);
        Http::fake(function ($request) use ($metas) {
            if ($request->url() === 'https://demo.test/page') {
                return Http::response('<html><head>'.$metas.'</head></html>', 200, ['Content-Type' => 'text/html']);
            }
            if (str_ends_with($request->url(), '.png')) {
                return Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='), 200, ['Content-Type' => 'image/png']);
            }

            return Http::response('', 404);
        });
    }

    public function test_twitter_fields_override_open_graph_and_use_their_own_image(): void
    {
        $this->page('<meta property="og:title" content="OG title"><meta property="og:image" content="/og.png"><meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="X title"><meta name="twitter:description" content="X description"><meta name="twitter:image" content="/x.png"><meta name="twitter:image:alt" content="X alt">');
        $response = $this->postJson('/preview', ['url' => 'https://demo.test/page'])
            ->assertOk()->assertJsonPath('title', 'OG title')->assertJsonPath('twitter.title', 'X title')
            ->assertJsonPath('twitter.card', 'summary_large_image')->assertJsonPath('twitter.description', 'X description')
            ->assertJsonPath('twitter.sources.image', 'twitter:image')->assertJsonPath('twitter.alt', 'X alt');
        $this->assertStringStartsWith('data:image/png;base64,', $response->json('twitter.image'));
        Http::assertSent(fn ($request) => $request->url() === 'https://demo.test/x.png');
    }

    public function test_open_graph_fallback_is_explicit_and_shared_image_is_fetched_once(): void
    {
        $this->page('<meta property="og:type" content="website"><meta property="og:title" content="OG title"><meta property="og:description" content="OG description"><meta property="og:image" content="/og.png">');
        $this->postJson('/preview', ['url' => 'https://demo.test/page'])->assertOk()
            ->assertJsonPath('twitter.card', 'summary')->assertJsonPath('twitter.inferred', true)
            ->assertJsonPath('twitter.sources.title', 'og:title')->assertJsonPath('twitter.checks.0.status', 'warn');
        Http::assertSentCount(2);
    }

    public function test_missing_and_invalid_card_types_are_not_rendered_as_large_cards(): void
    {
        $this->page('<title>HTML fallback</title>');
        $this->postJson('/preview', ['url' => 'https://demo.test/page'])->assertOk()
            ->assertJsonPath('twitter.previewable', false)->assertJsonPath('twitter.title', '');
        $this->page('<meta name="twitter:card" content="invalid"><meta name="twitter:title" content="Title">');
        $this->postJson('/preview', ['url' => 'https://demo.test/page'])->assertOk()
            ->assertJsonPath('twitter.previewable', false)->assertJsonPath('twitter.checks.0.status', 'fail');
    }

    public function test_unreachable_twitter_image_is_reported_without_losing_metadata(): void
    {
        $this->page('<meta name="twitter:card" content="summary"><meta name="twitter:title" content="Title"><meta name="twitter:image" content="https://demo.test/missing">');

        $response = $this->postJson('/preview', ['url' => 'https://demo.test/page'])->assertOk()
            ->assertJsonPath('twitter.title', 'Title')->assertJsonPath('twitter.image', null);
        $check = collect($response->json('twitter.checks'))->firstWhere('label', 'Twitter-Bild erreichbar');
        $this->assertSame('fail', $check['status']);
    }

    public function test_player_fields_and_duplicate_tags_are_checked(): void
    {
        $this->page('<meta name="twitter:card" content="player"><meta name="twitter:title" content="First"><meta name="twitter:title" content="Second"><meta name="twitter:player" content="http://demo.test/player"><meta name="twitter:player:width" content="0">');
        $response = $this->postJson('/preview', ['url' => 'https://demo.test/page'])->assertOk()->assertJsonPath('twitter.title', 'First');
        $checks = collect($response->json('twitter.checks'));
        $this->assertSame('fail', $checks->firstWhere('label', 'twitter:player')['status']);
        $this->assertSame('fail', $checks->firstWhere('label', 'twitter:player:height')['status']);
        $this->assertSame('warn', $checks->firstWhere('label', 'Mehrfaches twitter:title')['status']);
    }

    public function test_app_card_requires_platform_ids(): void
    {
        $this->page('<meta name="twitter:card" content="app">');
        $response = $this->postJson('/preview', ['url' => 'https://demo.test/page'])->assertOk();
        $check = collect($response->json('twitter.checks'))->firstWhere('label', 'App-Karte');
        $this->assertSame('fail', $check['status']);
    }
}
