<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\TrustProxies;

class TrustProxiesStub extends TrustProxies
{
    public function __construct() {}
}

class TrustProxiesTest extends TestCase
{
    public function testCanInstantiateTrustProxies()
    {
        $middleware = new TrustProxiesStub();
        $this->assertInstanceOf(TrustProxies::class, $middleware);
    }

    public function testProxiesPropertyIsStar()
    {
        $middleware = new TrustProxiesStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('proxies');
        $property->setAccessible(true);
        $proxies = $property->getValue($middleware);
        $this->assertEquals('*', $proxies);
    }

    public function testHeadersPropertyIsInt()
    {
        $middleware = new TrustProxiesStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('headers');
        $property->setAccessible(true);
        $headers = $property->getValue($middleware);
        $this->assertIsInt($headers);
    }

    public function testExtendsIlluminateTrustProxies()
    {
        $middleware = new TrustProxiesStub();
        $this->assertInstanceOf(\Illuminate\Http\Middleware\TrustProxies::class, $middleware);
    }

    public function testStaticMethodCannotBeTested()
    {
        $this->fail('Static method testing is not supported in this environment.');
    }
}
