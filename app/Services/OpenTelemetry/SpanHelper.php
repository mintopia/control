<?php

namespace App\Services\OpenTelemetry;

/**
 * Lightweight span helper wrapper so tests and application code can interact
 * with the tracer span/scope without depending on a concrete implementation.
 */
class SpanHelper
{
    protected $span;
    protected $scope;

    public function __construct($span, $scope)
    {
        $this->span = $span;
        $this->scope = $scope;
    }

    public function getSpan()
    {
        return $this->span;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function setAttribute(string $key, $value): void
    {
        if (is_object($this->span) && method_exists($this->span, 'setAttribute')) {
            $this->span->setAttribute($key, $value);
        }
    }

    public function addEvent(string $name, array $attributes = []): void
    {
        if (is_object($this->span) && method_exists($this->span, 'addEvent')) {
            $this->span->addEvent($name, $attributes);
        }
    }

    public function end(): void
    {
        if (is_object($this->span) && method_exists($this->span, 'end')) {
            $this->span->end();
        }

        // attempt to close scope if possible
        if (is_object($this->scope)) {
            if (method_exists($this->scope, 'detach')) {
                try {
                    $this->scope->detach();
                } catch (\Throwable $e) {
                    // noop
                }
            }
            if (method_exists($this->scope, 'close')) {
                try {
                    $this->scope->close();
                } catch (\Throwable $e) {
                    // noop
                }
            }
        }
    }
}
