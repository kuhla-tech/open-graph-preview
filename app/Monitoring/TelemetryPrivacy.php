<?php

namespace App\Monitoring;

use Sentry\Event;
use Sentry\Frame;
use Sentry\Logs\Log;
use Sentry\Metrics\Types\Metric;
use Sentry\Stacktrace;

class TelemetryPrivacy
{
    public const ATTRIBUTES = ['route', 'method', 'status_code', 'duration_ms', 'outcome', 'check_failures', 'check_warnings', 'sentry.environment', 'sentry.release', 'sentry.sdk.name', 'sentry.sdk.version', 'sentry.origin'];

    public static function event(Event $event): ?Event
    {
        $clean = Event::createEvent($event->getId());
        $clean->setTimestamp($event->getTimestamp())->setLevel($event->getLevel());
        $clean->setRelease($event->getRelease())->setEnvironment($event->getEnvironment());
        $exceptions = [];
        foreach ($event->getExceptions() as $exception) {
            $exception->setValue('Application failure (details omitted for privacy)');
            $trace = $exception->getStacktrace();
            if ($trace) {
                $frames = [];
                foreach ($trace->getFrames() as $frame) {
                    $frames[] = new Frame($frame->getFunctionName(), basename($frame->getFile()), $frame->getLine());
                }
                $exception->setStacktrace(new Stacktrace($frames));
            }
            $exceptions[] = $exception;
        }
        $clean->setExceptions($exceptions);
        if (! $exceptions) {
            $clean->setMessage('Application error (details omitted for privacy)');
        }

        return $clean;
    }

    public static function log(Log $log): ?Log
    {
        if (! in_array($log->getBody(), ['HTTP request completed', 'Monitoring smoke test'], true)) {
            return null;
        }
        foreach ($log->attributes()->all() as $key => $value) {
            if (! in_array($key, self::ATTRIBUTES, true)) {
                $log->attributes()->forget($key);
            }
        }

        return $log;
    }

    public static function metric(Metric $metric): ?Metric
    {
        if (! in_array($metric->getName(), ['opengraph.requests', 'opengraph.duration', 'opengraph.preview_requests', 'opengraph.preview_failures', 'opengraph.check_failures', 'opengraph.check_warnings'], true)) {
            return null;
        }
        foreach ($metric->getAttributes()->all() as $key => $value) {
            if (! in_array($key, self::ATTRIBUTES, true)) {
                $metric->getAttributes()->forget($key);
            }
        }

        return $metric;
    }
}
