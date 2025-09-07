<?php

namespace Tests\Unit\app\Http;

use App\Http\Kernel;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\MetricsCollector;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use ReflectionClass;
use Tests\TestCase;

class KernelTest extends TestCase
{
    public function testCanInstantiateKernel()
    {
        $kernel = new Kernel(app(), app('router'));
        $this->assertInstanceOf(Kernel::class, $kernel);
    }

    private function getProtectedProperty($object, string $property)
    {
        $reflection = new ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }

    public function testGlobalMiddlewareStackContainsExpectedMiddleware()
    {
        $kernel = new Kernel(app(), app('router'));
        $middleware = $this->getProtectedProperty($kernel, 'middleware');
        $this->assertContains(MetricsCollector::class, $middleware);
        $this->assertContains(TrustProxies::class, $middleware);
        $this->assertContains(ConvertEmptyStringsToNull::class, $middleware);
    }

    public function testMiddlewareGroupsContainExpectedMiddleware()
    {
        $kernel = new Kernel(app(), app('router'));
        $groups = $this->getProtectedProperty($kernel, 'middlewareGroups');
        $this->assertArrayHasKey('web', $groups);
        $this->assertContains(EncryptCookies::class, $groups['web']);
        $this->assertArrayHasKey('api', $groups);
        $this->assertContains(EnsureFrontendRequestsAreStateful::class, $groups['api']);
    }

    public function testMiddlewareAliasesContainExpectedAliases()
    {
        $kernel = new Kernel(app(), app('router'));
        $aliases = $this->getProtectedProperty($kernel, 'middlewareAliases');
        $this->assertArrayHasKey('auth', $aliases);
        $this->assertEquals(Authenticate::class, $aliases['auth']);
        $this->assertArrayHasKey('throttle', $aliases);
        $this->assertEquals(ThrottleRequests::class, $aliases['throttle']);
    }
}
