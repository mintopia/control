<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\RedirectIfAuthenticated;

class RedirectIfAuthenticatedStub extends RedirectIfAuthenticated {}

class RedirectIfAuthenticatedTest extends TestCase
{
    public function testCanInstantiateRedirectIfAuthenticated()
    {
        $middleware = new RedirectIfAuthenticatedStub();
        $this->assertInstanceOf(RedirectIfAuthenticated::class, $middleware);
    }

    public function testHandleFailsForStaticAuth()
    {
        $this->fail('Static method mocking for Auth::guard() is not supported in this environment.');
    }
}
