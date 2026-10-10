<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\InstanceSettings;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminBackendTest extends TestCase
{
    use RefreshDatabase;

    private function admin(bool $confirmed = true): User
    {
        $user = User::factory()->create(['password' => Hash::make('InitialPassword123!')]);
        $user->forceFill(['is_admin' => true])->save();
        $this->actingAs($user);
        if ($confirmed) {
            $this->withSession(['auth.password_confirmed_at' => time()]);
        }

        return $user;
    }

    public function test_admin_access_is_restricted_and_public_registration_is_disabled(): void
    {
        $this->get('/admin/settings/general')->assertRedirect('/admin/login');
        $this->post('/admin/settings/general', ['app_name' => 'Unauthorized'])->assertRedirect('/admin/login');
        $this->get('/admin/register')->assertNotFound();
        $this->actingAs(User::factory()->create());
        $this->get('/admin/settings/general')->assertForbidden();
        $this->assertDatabaseCount('instance_settings', 0);
    }

    public function test_password_login_rejects_non_admins_and_is_throttled(): void
    {
        $user = User::factory()->create(['password' => Hash::make('InitialPassword123!')]);
        $this->postJson('/admin/login', ['email' => $user->email, 'password' => 'InitialPassword123!'])->assertUnprocessable();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/admin/login', ['email' => 'no-admin@example.com', 'password' => 'bad']);
        }
        $this->postJson('/admin/login', ['email' => 'no-admin@example.com', 'password' => 'bad'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_settings_require_recent_password_confirmation_and_use_an_allowlist(): void
    {
        $this->admin(false);
        $this->post('/admin/settings/general', ['app_name' => 'Changed'])->assertRedirect('/admin/user/confirm-password');
        $this->assertDatabaseCount('instance_settings', 0);
        $this->withSession(['auth.password_confirmed_at' => time()]);
        $this->post('/admin/settings/general', ['app_name' => 'My Instance', 'app_locale' => 'en', 'session_lifetime' => 120, 'app_key' => 'forged', 'sentry_dsn' => 'https://forged.test/'])->assertRedirect();
        $this->assertDatabaseMissing('instance_settings', ['key' => 'app_key']);
        $this->assertDatabaseMissing('instance_settings', ['key' => 'sentry_dsn']);
        $this->get('/en/')->assertOk()->assertSee('My Instance')->assertSee('My Instance – Check link previews');
        $this->assertStringNotContainsString('My Instance', DB::table('instance_settings')->where('key', 'app_name')->value('payload'));
    }

    public function test_monitoring_secrets_are_encrypted_redacted_and_can_be_cleared(): void
    {
        $this->admin();
        $dsn = 'https://abc123@ingest.example.org/123';
        $fields = ['monitoring_enabled' => true, 'sentry_dsn' => $dsn, 'sentry_environment' => 'testing', 'sentry_release' => 'test', 'matomo_enabled' => false, 'matomo_url' => '', 'matomo_site_id' => 1, 'log_channel' => 'daily', 'log_level' => 'warning', 'log_daily_days' => 7];
        $this->post('/admin/settings/monitoring', $fields)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($dsn, app(InstanceSettings::class)->value('sentry_dsn'));
        $this->assertStringNotContainsString('abc123', DB::table('instance_settings')->where('key', 'sentry_dsn')->value('payload'));
        $this->get('/admin/settings/monitoring')->assertOk()->assertDontSee('abc123');
        $fields['sentry_dsn'] = '';
        $this->post('/admin/settings/monitoring', $fields)->assertSessionHasNoErrors();
        $this->assertSame($dsn, app(InstanceSettings::class)->value('sentry_dsn'));
        $fields['monitoring_enabled'] = false;
        $fields['clear_sentry_dsn'] = true;
        $this->post('/admin/settings/monitoring', $fields)->assertSessionHasNoErrors();
        $this->assertNull(app(InstanceSettings::class)->value('sentry_dsn'));
    }

    public function test_incomplete_analytics_settings_are_rejected(): void
    {
        $this->admin();
        $this->post('/admin/settings/monitoring', ['matomo_enabled' => true, 'matomo_url' => '', 'matomo_site_id' => 12, 'monitoring_enabled' => false, 'log_channel' => 'daily', 'log_level' => 'warning', 'log_daily_days' => 7])->assertSessionHasErrors('matomo_url');
        $this->assertDatabaseCount('instance_settings', 0);
    }

    public function test_legal_pages_are_instance_specific_bilingual_and_escape_custom_text(): void
    {
        $this->get('/de/impressum')->assertOk()->assertDontSee('Marcel Kuhla')->assertDontSee('Johannesstr. 21');
        $this->admin();
        $this->post('/admin/settings/legal', ['legal_controller_name' => 'Jane Example', 'legal_brand' => 'Example Org', 'legal_email' => 'legal@example.org'])->assertSessionHasNoErrors();
        $this->get('/en/impressum')->assertOk()->assertSee('Jane Example')->assertSee('legal@example.org')->assertDontSee('Marcel Kuhla');
        $this->post('/admin/settings/pages', ['imprint_de' => "Eigener Text\n<script>alert(1)</script>", 'imprint_en' => 'My own legal notice', 'privacy_de' => 'Eigener Datenschutz', 'privacy_en' => 'My own privacy notice'])->assertSessionHasNoErrors();
        $this->get('/de/impressum')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/en/impressum')->assertOk()->assertSee('My own legal notice')->assertDontSee('Jane Example');
        $this->get('/en/datenschutz')->assertOk()->assertSee('My own privacy notice');
    }

    public function test_uploads_generate_og_image_favicon_and_manifest_icons(): void
    {
        Storage::fake('public');
        $this->admin();
        $this->post('/admin/settings/branding', ['logo' => UploadedFile::fake()->image('logo.png', 120, 40), 'social_image' => UploadedFile::fake()->image('og.jpg', 900, 600), 'icon' => UploadedFile::fake()->image('icon.png', 600, 600)])->assertSessionHasNoErrors();
        $settings = app(InstanceSettings::class);
        $settings->apply();
        $this->assertCount(7, Storage::disk('public')->allFiles('branding'));
        foreach (['social_image' => [1200, 630], 'icon_32' => [32, 32], 'icon_512' => [512, 512]] as $key => $dimensions) {
            $filename = basename(config('branding.'.$key));
            $image = getimagesizefromstring(Storage::disk('public')->get('branding/'.$filename));
            $this->assertSame($dimensions, [$image[0], $image[1]]);
        }
        $ico = Storage::disk('public')->get('branding/'.basename(config('branding.favicon')));
        $this->assertSame("\x00\x00\x01\x00", substr($ico, 0, 4));
        $this->get('/de/')->assertOk()->assertSee(config('branding.social_image'), false)->assertSee(config('branding.logo'), false)->assertSee(config('branding.favicon'), false);
        $this->get('/en/app.webmanifest')->assertOk()->assertJsonPath('icons.0.sizes', '192x192')->assertJsonPath('icons.1.sizes', '512x512');
        $this->get(config('branding.icon_32'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_theme_specific_logos_replace_the_header_name_and_keep_legacy_fallbacks(): void
    {
        Storage::fake('public');
        $this->admin();
        $settings = app(InstanceSettings::class);
        $settings->put('asset_logo', '/media/branding/legacy-logo.png');
        $settings->apply();
        $this->get('/admin/settings/branding')->assertOk()->assertSee('name="logo_light"', false)->assertSee('name="logo_dark"', false)->assertSee('legacy-logo.png');
        $this->post('/admin/settings/branding', [
            'logo_light' => UploadedFile::fake()->image('light.png', 700, 100),
            'logo_dark' => UploadedFile::fake()->image('dark.png', 700, 100),
        ])->assertSessionHasNoErrors();
        $settings->apply();
        $light = config('branding.logo_light');
        $dark = config('branding.logo_dark');
        $this->assertNotSame($light, $dark);
        foreach (['/de/', '/en/impressum', '/admin/settings/branding'] as $page) {
            $html = $this->get($page)->assertOk()->assertSee($light, false)->assertSee($dark, false)->getContent();
            preg_match('/<a class="brand"[^>]*>(.*?)<\/a>/s', $html, $brand);
            $content = preg_replace('/<style>.*?<\/style>/s', '', $brand[1]);
            $this->assertSame('', trim(strip_tags($content)), 'Custom logo must replace visible project text.');
        }
        $this->post('/admin/settings/branding', ['clear_logo_dark' => 1])->assertSessionHasNoErrors();
        $settings->apply();
        $this->assertNull(config('branding.logo_dark'));
        $this->get('/de/')->assertSee($light, false)->assertDontSee($dark, false);
        $this->post('/admin/settings/branding', ['clear_logo_light' => 1])->assertSessionHasNoErrors();
        $settings->apply();
        $this->assertNull(config('branding.logo'));
        $this->get('/de/')->assertSee('<span class="logo">og</span>'.config('app.name'), false);
    }

    public function test_dark_only_logo_is_used_in_both_themes_and_invalid_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $this->admin();
        $this->post('/admin/settings/branding', ['logo_dark' => UploadedFile::fake()->createWithContent('bad.svg', '<svg></svg>')])->assertSessionHasErrors('logo_dark');
        $this->post('/admin/settings/branding', ['logo_dark' => UploadedFile::fake()->image('dark.png', 500, 100)])->assertSessionHasNoErrors();
        app(InstanceSettings::class)->apply();
        $html = $this->get('/de/')->assertOk()->getContent();
        $this->assertStringContainsString('class="brand-logo-light" src="'.url(config('branding.logo_dark')).'"', $html);
        $this->assertStringContainsString('class="brand-logo-dark" src="'.url(config('branding.logo_dark')).'"', $html);
    }

    public function test_svg_and_malformed_images_are_rejected(): void
    {
        Storage::fake('public');
        $this->admin();
        $this->post('/admin/settings/branding', ['logo' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')])->assertSessionHasErrors('logo');
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->get('/media/branding/not-an-image.txt')->assertNotFound();
    }

    public function test_localized_social_images_are_cropped_and_selected_by_page_language(): void
    {
        Storage::fake('public');
        $this->admin();
        $settings = app(InstanceSettings::class);
        $settings->put('asset_social_image', '/images/shared-image.png');
        $settings->apply();
        $this->post('/admin/settings/branding', [
            'social_image_de' => UploadedFile::fake()->image('german.png', 800, 800),
            'social_image_en' => UploadedFile::fake()->image('english.png', 1500, 700),
        ])->assertSessionHasNoErrors();
        $settings->apply();
        foreach (['de', 'en'] as $locale) {
            $image = config('branding.social_image_'.$locale);
            $dimensions = getimagesizefromstring(Storage::disk('public')->get('branding/'.basename($image)));
            $this->assertSame([1200, 630], [$dimensions[0], $dimensions[1]]);
            $html = $this->get('/'.$locale.'/')->assertOk()->getContent();
            $this->assertStringContainsString('property="og:image" content="'.url($image).'"', $html);
            $this->assertStringContainsString('name="twitter:image" content="'.url($image).'"', $html);
            $this->get($image)->assertOk();
        }
        $this->post('/admin/settings/branding', ['clear_social_image_en' => 1])->assertSessionHasNoErrors();
        $settings->apply();
        $this->get('/en/')->assertSee('property="og:image" content="'.url('/images/shared-image.png').'"', false);
        $this->post('/admin/settings/branding', ['clear_social_image' => 1])->assertSessionHasNoErrors();
        $settings->apply();
        $this->get('/en/')->assertSee('social-preview-en.png');
        $this->get('/de/')->assertSee(config('branding.social_image_de'), false);
        $this->post('/admin/settings/branding', ['social_image_en' => UploadedFile::fake()->createWithContent('bad.svg', '<svg/>')])->assertSessionHasErrors('social_image_en');
    }

    public function test_failed_asset_writes_do_not_save_broken_branding_settings(): void
    {
        $this->admin();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        $this->post('/admin/settings/branding', ['logo' => UploadedFile::fake()->image('logo.png', 50, 50)])->assertSessionHasErrors('logo');
        $this->assertDatabaseMissing('instance_settings', ['key' => 'asset_logo']);
    }

    public function test_signed_setup_link_is_required_and_is_consumed_once(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true, 'password_setup_pending' => true])->save();
        $token = Password::broker()->createToken($user);
        $url = URL::temporarySignedRoute('admin.setup', now()->addMinutes(60), ['user' => $user->id, 'token' => $token]);
        $this->get('/admin/setup/'.$user->id.'?token='.$token)->assertForbidden();
        $this->get($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->post($url, ['password' => 'MyNewPassword123!', 'password_confirmation' => 'MyNewPassword123!'])->assertRedirect('/admin/security');
        $this->assertTrue(Hash::check('MyNewPassword123!', $user->fresh()->password));
        $this->get($url)->assertForbidden();
        $this->assertAuthenticatedAs($user);
    }

    public function test_setup_renewal_is_limited_to_uninitialized_administrators(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();
        $this->artisan('opengraph:admin', ['--email' => $user->email, '--renew-setup' => true])->assertFailed();
        $user->forceFill(['password_setup_pending' => true])->save();
        $this->artisan('opengraph:admin', ['--email' => $user->email, '--renew-setup' => true])->assertSuccessful();
    }

    public function test_two_factor_confirmation_and_recovery_code_login(): void
    {
        $user = $this->admin();
        $this->post('/admin/user/two-factor-authentication')->assertRedirect();
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $secret = decrypt($user->two_factor_secret);
        $this->assertNotSame($secret, $user->two_factor_secret);
        $this->get('/admin/security')->assertOk()->assertSee('<svg', false);
        $this->post('/admin/user/confirmed-two-factor-authentication', ['code' => (new Google2FA)->getCurrentOtp($secret)])->assertRedirect();
        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
        $recovery = $user->recoveryCodes()[0];
        $this->post('/admin/logout');
        $this->postJson('/admin/login', ['email' => $user->email, 'password' => 'InitialPassword123!'])->assertOk()->assertJsonPath('two_factor', true);
        $this->assertGuest();
        $this->postJson('/admin/two-factor-challenge', ['recovery_code' => $recovery])->assertNoContent();
        $this->assertAuthenticatedAs($user);
        $this->assertNotContains($recovery, $user->fresh()->recoveryCodes());
    }

    public function test_passkey_endpoints_require_fresh_confirmation_and_ownership(): void
    {
        $this->getJson('/admin/passkeys/login/options')->assertOk()->assertJsonPath('options.rpId', 'og.test');
        $user = $this->admin(false);
        $this->getJson('/admin/user/passkeys/options')->assertStatus(423);
        $this->withSession(['auth.password_confirmed_at' => time()]);
        $this->getJson('/admin/user/passkeys/options')->assertOk()->assertJsonPath('options.rp.id', 'og.test');
        $other = User::factory()->create();
        $passkey = $other->passkeys()->create(['name' => 'Other device', 'credential_id' => 'other-key', 'credential' => []]);
        $this->deleteJson('/admin/user/passkeys/'.$passkey->id)->assertForbidden();
        $this->postJson('/admin/user/passkeys', ['name' => 'Fake key', 'credential' => []])->assertUnprocessable();
        $this->assertDatabaseCount('passkeys', 1);
    }

    public function test_passkey_registration_and_signed_login_verify_real_webauthn_credentials(): void
    {
        $user = $this->admin();
        $options = $this->getJson('/admin/user/passkeys/options')->assertOk()->json('options');
        $b64 = fn (string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
        $cbor = function (string $bytes, int $major): string {
            $length = strlen($bytes);

            return ($length < 24 ? chr(($major << 5) | $length) : ($length < 256 ? chr(($major << 5) | 24).chr($length) : chr(($major << 5) | 25).pack('n', $length))).$bytes;
        };
        $privateKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $details = openssl_pkey_get_details($privateKey);
        $publicKey = "\xa5\x01\x02\x03\x26\x20\x01\x21".$cbor($details['ec']['x'], 2)."\x22".$cbor($details['ec']['y'], 2);
        $credentialId = random_bytes(32);
        $rpHash = hash('sha256', 'og.test', true);
        $authData = $rpHash."\x45".pack('N', 0).str_repeat("\x00", 16).pack('n', strlen($credentialId)).$credentialId.$publicKey;
        $attestation = "\xa3".$cbor('fmt', 3).$cbor('none', 3).$cbor('attStmt', 3)."\xa0".$cbor('authData', 3).$cbor($authData, 2);
        $clientData = json_encode(['type' => 'webauthn.create', 'challenge' => $options['challenge'], 'origin' => 'https://og.test', 'crossOrigin' => false]);
        $credential = ['id' => $b64($credentialId), 'rawId' => $b64($credentialId), 'type' => 'public-key', 'response' => ['clientDataJSON' => $b64($clientData), 'attestationObject' => $b64($attestation), 'transports' => ['internal']]];
        $this->postJson('/admin/user/passkeys', ['name' => 'Test authenticator', 'credential' => $credential])->assertOk();
        $this->assertDatabaseCount('passkeys', 1);
        $this->post('/admin/logout');
        $options = $this->getJson('/admin/passkeys/login/options')->assertOk()->json('options');
        $clientData = json_encode(['type' => 'webauthn.get', 'challenge' => $options['challenge'], 'origin' => 'https://og.test', 'crossOrigin' => false]);
        $authData = $rpHash."\x05".pack('N', 1);
        openssl_sign($authData.hash('sha256', $clientData, true), $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $credential['response'] = ['clientDataJSON' => $b64($clientData), 'authenticatorData' => $b64($authData), 'signature' => $b64($signature), 'userHandle' => $b64($user->getPasskeyUserHandle())];
        $this->postJson('/admin/passkeys/login', ['credential' => $credential])->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->passkeys()->first()->last_used_at);
        $this->post('/admin/logout');
        $this->getJson('/admin/passkeys/login/options');
        $this->postJson('/admin/passkeys/login', ['credential' => $credential])->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_admin_pages_render_without_analytics_in_both_languages(): void
    {
        $this->admin();
        foreach (['de', 'en'] as $locale) {
            $this->withSession(['locale' => $locale]);
            foreach (['general', 'branding', 'search', 'monitoring', 'legal', 'pages'] as $section) {
                $this->get('/admin/settings/'.$section)->assertOk()->assertDontSee('matomo.js')->assertSee('lang="'.$locale.'"', false)->assertHeader('Cache-Control', 'no-store, private');
            }
            $this->get('/admin/security')->assertOk()->assertDontSee('matomo.js');
        }
    }
}
