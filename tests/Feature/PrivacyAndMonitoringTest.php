<?php

namespace Tests\Feature;

use App\Monitoring\PrivateLogProcessor;
use App\Monitoring\TelemetryPrivacy;
use App\Services\TargetUrlPolicy;
use Monolog\Level;
use Monolog\LogRecord;
use Sentry\Event;
use Sentry\ExceptionDataBag;
use Sentry\Frame;
use Sentry\Logs\Log;
use Sentry\Logs\LogLevel;
use Sentry\Stacktrace;
use Tests\TestCase;

class PrivacyAndMonitoringTest extends TestCase
{
    public function test_legal_pages_and_consent_are_bilingual(): void
    {
        config(['legal.controller_name' => 'Example Operator', 'legal.address' => 'Example Street 1', 'legal.hosting_location' => 'Bremen, Deutschland']);
        foreach (['de', 'en'] as $locale) {
            $this->get('/'.$locale.'/impressum')->assertOk()->assertSee('Example Operator')->assertSee('Example Street 1')->assertDontSee('Marcel Kuhla');
            $response = $this->get('/'.$locale.'/datenschutz')->assertOk()->assertSee('Matomo')->assertSee('Bremen')->assertSee('Sentry');
            preg_match('/<article>(.*?)<\/article>/s', $response->getContent(), $privacyContent);
            $this->assertStringNotContainsString('analytics.kuhla.tech', $privacyContent[1]);
        }
        config(['monitoring.matomo_enabled' => true, 'monitoring.matomo_url' => 'https://analytics.example.com', 'monitoring.matomo_site_id' => 12]);
        $this->get('/en/')->assertOk()->assertSee('Visitor statistics with Matomo')->assertSee('requireConsent')->assertDontSee("sessionStorage.setItem('og-last-url'", false)->assertDontSee('<script src="https://analytics.kuhla.tech', false);
    }

    public function test_disabled_analytics_still_displays_privacy_settings(): void
    {
        config(['monitoring.matomo_enabled' => false, 'monitoring.enabled' => false]);
        $this->get('/de/')->assertOk()->assertSee('Die Besucheranalyse ist in dieser Installation deaktiviert.')->assertSee('data-analytics-settings', false)->assertSee('--scrollbar-thumb', false)->assertDontSee('data-analytics-choice="yes"', false);
        $this->get('/en/datenschutz')->assertOk()->assertSee('Visitor analytics are disabled in this installation.')->assertSee('Got it');
    }

    public function test_error_events_do_not_contain_urls_pii_or_stack_variables(): void
    {
        $secret = 'https://secret.test/private?token=topsecret';
        $event = Event::createEvent();
        $event->setRequest(['url' => $secret, 'data' => ['url' => $secret], 'headers' => ['Cookie' => 'private']]);
        $event->setExtra(['url' => $secret]);
        $event->setContext('data', ['url' => $secret]);
        $event->setExceptions([new ExceptionDataBag(new \RuntimeException($secret), new Stacktrace([new Frame('fetch', '/private/path/app.php', 42, vars: ['url' => $secret])]))]);
        $clean = TelemetryPrivacy::event($event);
        $this->assertSame([], $clean->getRequest());
        $this->assertSame([], $clean->getExtra());
        $this->assertSame([], $clean->getContexts());
        $this->assertNull($clean->getUser());
        $this->assertStringNotContainsString($secret, $clean->getExceptions()[0]->getValue());
        $this->assertSame([], $clean->getExceptions()[0]->getStacktrace()->getFrames()[0]->getVars());
    }

    public function test_logs_allow_only_fixed_messages_and_safe_attributes(): void
    {
        $log = new Log(microtime(true), str_repeat('a', 32), LogLevel::info(), 'HTTP request completed');
        $log->setAttribute('url', 'https://secret.test/')->setAttribute('route', 'preview')->setAttribute('user.ip', '127.0.0.1');
        $clean = TelemetryPrivacy::log($log);
        $this->assertSame(['route' => 'preview'], $clean->attributes()->toSimpleArray());
        $this->assertNull(TelemetryPrivacy::log(new Log(microtime(true), str_repeat('a', 32), LogLevel::error(), 'https://secret.test/')));
        $record = new LogRecord(new \DateTimeImmutable, 'app', Level::Error, 'URL https://secret.test', ['exception' => new \RuntimeException('secret'), 'url' => 'private']);
        $private = (new PrivateLogProcessor)($record);
        $this->assertSame([], $private->context);
        $this->assertStringNotContainsString('secret', $private->message);
    }

    public function test_hosted_mode_blocks_local_and_reserved_targets(): void
    {
        config(['monitoring.allow_private_targets' => false]);
        $policy = new TargetUrlPolicy;
        foreach (['127.0.0.1', '10.0.0.1', '100.64.0.1', '192.0.2.1', '::1', '::ffff:127.0.0.1', '2001:db8::1'] as $ip) {
            $this->assertFalse($policy->publicIp($ip));
        }
        $this->assertTrue($policy->publicIp('1.1.1.1'));
        $this->expectException(\RuntimeException::class);
        $policy->options('http://127.0.0.1');
    }
}
