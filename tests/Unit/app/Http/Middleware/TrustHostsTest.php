<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\TrustHosts;

class TrustHostsTest extends TestCase
{
    public function testCanInstantiateTrustHosts()
    {
        $middleware = new TrustHosts();
        $this->assertInstanceOf(TrustHosts::class, $middleware);
    }
}
