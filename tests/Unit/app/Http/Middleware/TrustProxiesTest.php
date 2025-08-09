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

    public function testCustomProxiesPropertyInSubclass()
    {
        $middleware = new class extends TrustProxies {
            protected $proxies = ['10.0.0.1', '10.0.0.2'];
            public function __construct() {}
        };
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('proxies');
        $property->setAccessible(true);
        $proxies = $property->getValue($middleware);
        $this->assertEquals(['10.0.0.1', '10.0.0.2'], $proxies);
    }

    public function testCustomHeadersPropertyInSubclass()
    {
        $middleware = new class extends TrustProxies {
            protected $headers = 12345;
            public function __construct() {}
        };
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('headers');
        $property->setAccessible(true);
        $headers = $property->getValue($middleware);
        $this->assertEquals(12345, $headers);
    }

    public function testDefaultHeadersIncludesAllExpectedFlags()
    {
        $middleware = new TrustProxiesStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('headers');
        $property->setAccessible(true);
        $headers = $property->getValue($middleware);
        $expectedFlags = [
            \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR,
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST,
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT,
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO,
            \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB,
        ];
        foreach ($expectedFlags as $flag) {
            $this->assertTrue(($headers & $flag) === $flag, "Header flag $flag missing");
        }
    }
}
