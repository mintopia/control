<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\RedirectOnFirstLoginMiddleware;

class RedirectOnFirstLoginMiddlewareTest extends TestCase
{
    public function testCanInstantiateRedirectOnFirstLoginMiddleware()
    {
        $middleware = new RedirectOnFirstLoginMiddleware();
        $this->assertInstanceOf(RedirectOnFirstLoginMiddleware::class, $middleware);
    }
}
