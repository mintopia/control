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

    //CHECK whether more advanced tests are needed here and/or whether the stub can be improved
}
