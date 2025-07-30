<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\RedirectIfAuthenticated;

class RedirectIfAuthenticatedTest extends TestCase
{
    public function testCanInstantiateRedirectIfAuthenticated()
    {
        $middleware = new RedirectIfAuthenticated();
        $this->assertInstanceOf(RedirectIfAuthenticated::class, $middleware);
    }
}
