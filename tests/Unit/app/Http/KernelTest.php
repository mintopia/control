<?php

namespace Tests\Unit\app\Http;

use Tests\TestCase;
use App\Http\Kernel;

class KernelTest extends TestCase
{
    public function testCanInstantiateKernel()
    {
        $kernel = new Kernel(app(), app('router'));
        $this->assertInstanceOf(Kernel::class, $kernel);
    }

    private function getProtectedProperty($object, string $property)
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }

    public function testGlobalMiddlewareStackContainsExpectedMiddleware()
    {
        $kernel = new Kernel(app(), app('router'));
        $middleware = $this->getProtectedProperty($kernel, 'middleware');
        $this->assertContains(\App\Http\Middleware\MetricsCollector::class, $middleware);
        $this->assertContains(\App\Http\Middleware\TrustProxies::class, $middleware);
        $this->assertContains(\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class, $middleware);
    }

    public function testMiddlewareGroupsContainExpectedMiddleware()
    {
        $kernel = new Kernel(app(), app('router'));
        $groups = $this->getProtectedProperty($kernel, 'middlewareGroups');
        $this->assertArrayHasKey('web', $groups);
        $this->assertContains(\App\Http\Middleware\EncryptCookies::class, $groups['web']);
        $this->assertArrayHasKey('api', $groups);
        $this->assertContains(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, $groups['api']);
    }

    public function testMiddlewareAliasesContainExpectedAliases()
    {
        $kernel = new Kernel(app(), app('router'));
        $aliases = $this->getProtectedProperty($kernel, 'middlewareAliases');
        $this->assertArrayHasKey('auth', $aliases);
        $this->assertEquals(\App\Http\Middleware\Authenticate::class, $aliases['auth']);
        $this->assertArrayHasKey('throttle', $aliases);
        $this->assertEquals(\Illuminate\Routing\Middleware\ThrottleRequests::class, $aliases['throttle']);
    }
}
