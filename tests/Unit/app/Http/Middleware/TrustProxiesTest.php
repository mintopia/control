<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\TrustProxies;

class TrustProxiesTest extends TestCase
{
    public function testCanInstantiateTrustProxies()
    {
        $middleware = new TrustProxies();
        $this->assertInstanceOf(TrustProxies::class, $middleware);
    }
}
