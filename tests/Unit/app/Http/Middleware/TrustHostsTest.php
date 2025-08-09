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

    public function testHostsReturnsNullIfAllSubdomainsReturnsNull()
    {
        $app = app();
        $stub = new class($app) extends TrustHosts {
            public function allSubdomainsOfApplicationUrl()
            {
                return null;
            }
        };
        $hosts = $stub->hosts();
        $this->assertIsArray($hosts);
        $this->assertCount(1, $hosts);
        $this->assertNull($hosts[0]);
    }

    public function testHostsReturnsCustomString()
    {
        $app = app();
        $stub = new class($app) extends TrustHosts {
            public function allSubdomainsOfApplicationUrl()
            {
                return 'subdomain.example.org';
            }
        };
        $hosts = $stub->hosts();
        $this->assertEquals(['subdomain.example.org'], $hosts);
    }

    public function testHostsAlwaysReturnsArray()
    {
        $app = app();
        $stub = new class($app) extends TrustHosts {
            public function allSubdomainsOfApplicationUrl()
            {
                return 123;
            }
        };
        $hosts = $stub->hosts();
        $this->assertIsArray($hosts);
        $this->assertEquals([123], $hosts);
    }

    public function testAllSubdomainsOfApplicationUrlCanBeMocked()
    {
        $app = app();
        $mock = \Mockery::mock(TrustHosts::class, [$app])->makePartial();
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('allSubdomainsOfApplicationUrl')->andReturn('mocked.example.com');
        $this->assertEquals(['mocked.example.com'], $mock->hosts());
    }
}
