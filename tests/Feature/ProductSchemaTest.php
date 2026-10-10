<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductSchemaTest extends TestCase
{
    public function test_graph_product_with_image_object_is_checked_and_used_for_teams(): void
    {
        $json = json_encode(['@context' => 'https://schema.org', '@graph' => [
            ['@type' => ['Product', 'SoftwareApplication'], 'name' => 'Schema product',
                'description' => 'Schema description', 'url' => 'https://demo.test/',
                'image' => [['@type' => 'ImageObject', 'contentUrl' => '/product.png']]],
        ]]);
        Http::fake([
            'https://demo.test/' => Http::response('<html><head><script type="application/ld+json">'.$json.'</script></head></html>', 200, ['Content-Type' => 'text/html']),
            'https://demo.test/product.png' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='), 200),
        ]);
        $response = $this->postJson('/preview', ['url' => 'https://demo.test/'])->assertOk()
            ->assertJsonPath('schema.preview.title', 'Schema product')
            ->assertJsonPath('schema.preview.description', 'Schema description')
            ->assertJsonPath('schema.checks.1.status', 'pass');
        $this->assertStringStartsWith('data:image/png;base64,', $response->json('schema.preview.image'));
        Http::assertSentCount(2);
    }

    public function test_invalid_json_is_reported_without_breaking_open_graph_checks(): void
    {
        Http::fake(['*' => Http::response('<html><head><meta property="og:title" content="Good"><script type="application/ld+json">{invalid}</script></head></html>', 200, ['Content-Type' => 'text/html'])]);
        $this->postJson('/preview?lang=en', ['url' => 'https://demo.test/'])->assertOk()
            ->assertJsonPath('title', 'Good')->assertJsonPath('schema.preview', null)
            ->assertJsonPath('schema.checks.0.status', 'fail')
            ->assertJsonPath('schema.checks.0.detail', 'Invalid JSON in the JSON-LD block. Check syntax and nesting.');
    }

    public function test_missing_product_fields_are_reported_and_schema_context_is_required(): void
    {
        $json = json_encode([['@context' => 'https://schema.org', '@type' => 'Product'], ['@context' => 'https://other.test', '@type' => 'Product', 'name' => 'Not schema.org']]);
        Http::fake(['*' => Http::response('<html><head><script type="application/ld+json">'.$json.'</script></head></html>', 200, ['Content-Type' => 'text/html'])]);
        $response = $this->postJson('/preview', ['url' => 'https://demo.test/'])->assertOk()->assertJsonPath('schema.preview', null);
        $this->assertCount(1, $response->json('schema.products'));
        $checks = collect($response->json('schema.checks'));
        $this->assertSame('fail', $checks->firstWhere('label', 'Product #1 · name')['status']);
        $this->assertSame('warn', $checks->firstWhere('label', 'Product #1 · image')['status']);
    }
}
