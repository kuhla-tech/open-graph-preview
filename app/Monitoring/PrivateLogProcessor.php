<?php

namespace App\Monitoring;

use Monolog\LogRecord;

class PrivateLogProcessor
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $allowed = array_intersect_key($record->context, array_flip(['route', 'method', 'status_code', 'duration_ms', 'outcome']));

        return $record->with(message: 'Application log entry (details omitted for privacy)', context: $allowed, extra: []);
    }
}
