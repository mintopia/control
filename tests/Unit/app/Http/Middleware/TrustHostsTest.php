<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\TrustHosts;

class TrustHostsStub extends TrustHosts
{
    public function __construct() {}
    public function allSubdomainsOfApplicationUrl()
    {
        return '*.example.com';
    }
}

class TrustHostsTest extends TestCase
{
    public function testCanInstantiateTrustHosts()
    {
        $middleware = new TrustHostsStub();
        $this->assertInstanceOf(TrustHosts::class, $middleware);
    }

    public function testHostsReturnsExpectedPattern()
    {
        $middleware = new TrustHostsStub();
        $hosts = $middleware->hosts();
        $this->assertIsArray($hosts);
        $this->assertEquals(['*.example.com'], $hosts);
    }

    public function testExtendsIlluminateTrustHosts()
    {
        $middleware = new TrustHostsStub();
        $this->assertInstanceOf(\Illuminate\Http\Middleware\TrustHosts::class, $middleware);
    }

    public function testStaticMethodCannotBeTested()
    {
        $this->fail('Static method testing is not supported in this environment.');
    }
}
