<?php

namespace App\Monitoring;

class PrivateLogTap
{
    public function __invoke($logger): void
    {
        $logger->pushProcessor(new PrivateLogProcessor);
    }
}
