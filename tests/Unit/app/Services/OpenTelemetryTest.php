<?php

namespace Tests\Unit\app\Services;

use Tests\TestCase;
use App\Services\OpenTelemetry;
use App\Services\OpenTelemetry\SpanHelper;

class OpenTelemetryTest extends TestCase
{
    // Ensure the tracer provider receives a non-null service name/version in tests
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'open-telemetry.service.name' => 'test-service',
            'open-telemetry.service.version' => '1.0',
        ]);
    }

    public function testStartSpanReturnsSpanHelper()
    {
        $spanHelper = OpenTelemetry::startSpan('test-span');
        $this->assertInstanceOf(SpanHelper::class, $spanHelper);
        // Ensure we close the span so the SDK doesn't complain about leaked scopes
        $spanHelper->end();
    }

    public function testStartSpanWithEmptyNameDefaults()
    {
        $spanHelper = OpenTelemetry::startSpan('');
        $this->assertInstanceOf(SpanHelper::class, $spanHelper);
        $spanHelper->end();
    }

    public function testSpanHelperSetAttributeAndAddEvent()
    {
        $spanHelper = OpenTelemetry::startSpan('attribute-event-span');
        // these methods are defensive; they should not throw
        $spanHelper->setAttribute('test.key', 'value');
        $spanHelper->addEvent('test.event', ['k' => 'v']);

        $this->assertInstanceOf(SpanHelper::class, $spanHelper);
        $spanHelper->end();
    }

    public function testSpanHelperEndDoesNotThrow()
    {
        $spanHelper = OpenTelemetry::startSpan('end-span');
        $spanHelper->end();

        $this->assertInstanceOf(SpanHelper::class, $spanHelper);
    }
}
