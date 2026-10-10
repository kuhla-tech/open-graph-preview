<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    public function test_homepage_can_be_switched_to_english_and_language_is_saved(): void
    {
        $this->get('/en/')->assertOk()->assertSee('lang="en"', false)
            ->assertSee('One link. The first impression.')->assertSee('No card type', false)
            ->assertSee('social-preview-en.png', false)->assertSee('en_US', false);
        $this->get('/')->assertRedirect(config('app.url').'/en/');
        $this->get('/de/')->assertSee('Ein Link. Der erste Eindruck.');
        $this->get('/de/')->assertSee('Ein Link. Der erste Eindruck.');
    }

    public function test_unsupported_locale_is_ignored(): void
    {
        $this->get('/?lang=fr')->assertRedirect(config('app.url').'/de/');
        $this->get('/fr/')->assertNotFound();
    }

    public function test_english_api_translates_checks_and_preserves_website_content(): void
    {
        Http::fake(['*' => Http::response('<html><head><meta property="og:title" content="Original deutscher Titel"></head></html>', 200, ['Content-Type' => 'text/html'])]);
        $this->get('/en/');
        $this->postJson('/en/preview', ['url' => 'https://demo.test/'])->assertOk()
            ->assertJsonPath('title', 'Original deutscher Titel')
            ->assertJsonPath('checks.1.detail', 'Required field is missing or empty.')
            ->assertJsonPath('twitter.checks.0.detail', 'Missing. Specify a card type explicitly; no card can be inferred.');
        $this->postJson('/preview', [])->assertStatus(422)->assertJsonPath('errors.url.0', 'Please enter a URL.');
    }

    public function test_english_errors_are_translated(): void
    {
        $this->postJson('/preview?lang=en', ['url' => 'file:///secret'])->assertStatus(422)
            ->assertJsonPath('message', 'Only complete HTTP and HTTPS URLs without credentials are allowed.');
    }

    public function test_legacy_links_redirect_and_locale_paths_take_precedence(): void
    {
        $this->get('/?lang=en')->assertStatus(301)->assertRedirect(config('app.url').'/en/');
        $this->get('/datenschutz?lang=de')->assertStatus(301)->assertRedirect(config('app.url').'/de/datenschutz');
        $this->get('/en/?lang=de')->assertStatus(301)->assertRedirect(config('app.url').'/en/');
        $this->get('/de/')->assertOk()->assertSee('lang="de"', false)->assertSee(config('app.url').'/en/', false)->assertDontSee('?lang=', false);
        $this->get('/en/datenschutz')->assertOk()->assertSee(config('app.url').'/de/datenschutz', false);
        $this->postJson('/en/preview', [])->assertStatus(422)->assertJsonPath('errors.url.0', 'Please enter a URL.');
    }

    public function test_manifest_is_localized(): void
    {
        $this->get('/en/app.webmanifest')->assertOk()
            ->assertJsonPath('lang', 'en')->assertJsonPath('start_url', '/en/')
            ->assertJsonPath('description', 'Check link previews and social metadata.');
    }
}
