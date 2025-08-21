<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\VerifyCsrfToken;

class VerifyCsrfTokenStub extends VerifyCsrfToken
{
    public function __construct() {}
}

class VerifyCsrfTokenTest extends TestCase
{
    public function testCanInstantiateVerifyCsrfToken()
    {
        $middleware = new VerifyCsrfTokenStub();
        $this->assertInstanceOf(VerifyCsrfToken::class, $middleware);
    }

    public function testExceptPropertyContainsWebhookTickets()
    {
        $middleware = new VerifyCsrfTokenStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);
        $this->assertContains('/webhooks/tickets/*', $except);
    }

    public function testExtendsIlluminateVerifyCsrfToken()
    {
        $middleware = new VerifyCsrfTokenStub();
        $this->assertInstanceOf(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class, $middleware);
    }

    public function testExceptPropertyIsArrayAndContainsWebhookPattern()
    {
        $middleware = new VerifyCsrfTokenStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);
        $this->assertIsArray($except);
        $this->assertNotEmpty($except);
        $this->assertStringContainsString('webhooks/tickets', $except[0]);
        $this->assertStringEndsWith('*', $except[0]);
    }

    public function testWebhookPatternMatchesSampleUri()
    {
        $middleware = new VerifyCsrfTokenStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);

        $pattern = $except[0];
        // normalize wildcard: remove trailing '*' and ensure sample starts with prefix
        $prefix = rtrim($pattern, '*');
        $this->assertStringStartsWith($prefix, '/webhooks/tickets/123');
    }

    public function testExceptPropertyIsProtected()
    {
        $middleware = new VerifyCsrfTokenStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $this->assertTrue($property->isProtected());
    }
}
