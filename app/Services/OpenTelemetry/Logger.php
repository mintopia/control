<?php

namespace App\Services\OpenTelemetry;

use Monolog\Handler\NullHandler;
use OpenTelemetry\API\Globals;
use OpenTelemetry\Contrib\Logs\Monolog\Handler;

class Logger
{
    /**
     * @param array<string, mixed> $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config): \Monolog\Logger
    {
        $level = $config['level'] ?? 'debug';
        if (config('services.open_telemetry.enabled')) {
            $handler = new Handler(
                Globals::loggerProvider(),
                $level,
            );
        } else {
            $handler = new NullHandler($level);
        }
        return new \Monolog\Logger($config['name'] ?? 'opentelemetry', [$handler]);
    }
}
