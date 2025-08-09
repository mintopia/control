<?php

namespace Tests\Unit\app\Services;

use Tests\TestCase;
use App\Services\OpenTelemetry;
use App\Services\OpenTelemetry\SpanHelper;

class OpenTelemetryTest extends TestCase
{
    //CHECK is this even needed?
    /*
    * config('open-telemetry.service.name') returns null, but OpenTelemetry\API\Trace\NoopTracerProvider::getTracer() requires a string as the first argument; you should provide a default string value if the config is missing.
            $tracer = Globals::tracerProvider()->getTracer(
            $serviceName ?? config('open-telemetry.service.name') ?? 'default-service',
            $version ?? config('open-telemetry.service.version') ?? '1.0.0',
            'https://opentelemetry.io/schemas/1.24.0'
        );
    */
    // public function testStartSpanReturnsSpanHelper()
    // {
    //     $spanHelper = OpenTelemetry::startSpan('test-span');
    //     $this->assertInstanceOf(SpanHelper::class, $spanHelper);
    // }
}
