<?php

namespace Tests\Feature;

use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    public function test_teams_product_data_contains_real_localized_product_and_image(): void
    {
        config(['branding.owner_name' => 'Example Company', 'branding.owner_url' => 'https://example.org/']);
        foreach (['de', 'en'] as $locale) {
            $response = $this->get('/'.$locale.'/')->assertOk();
            preg_match('/<script type="application\/ld\+json">\s*(.*?)\s*<\/script>/s', $response->getContent(), $matches);
            $schema = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('https://schema.org', $schema['@context']);
            $this->assertSame('Product', $schema['@type']);
            $this->assertSame('Open Graph Studio', $schema['name']);
            $this->assertSame('Organization', $schema['brand']['@type']);
            $this->assertSame('Example Company', $schema['brand']['name']);
            $this->assertSame('https://example.org/', $schema['brand']['url']);
            $this->assertStringContainsString($locale === 'en' ? 'social-preview-en.png' : 'social-preview-v3.png', $schema['image']);
            $this->assertStringContainsString($locale === 'en' ? 'Check social metadata' : 'Prüfe Social-Metadaten', $schema['description']);
            $this->assertArrayNotHasKey('offers', $schema);
        }
    }
}
