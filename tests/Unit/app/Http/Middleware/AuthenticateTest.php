<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\Authenticate;
use Illuminate\Http\Request;
use Mockery;

class AuthenticateStub extends Authenticate
{
    public function __construct() {}
}

class AuthenticateTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function callProtected($object, $method, $args = [])
    {
        $ref = new \ReflectionClass($object);
        $meth = $ref->getMethod($method);
        $meth->setAccessible(true);
        return $meth->invokeArgs($object, $args);
    }

    public function testRedirectToReturnsNullForJson()
    {
        $middleware = new AuthenticateStub();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('expectsJson')->andReturn(true);

        $this->assertNull($this->callProtected($middleware, 'redirectTo', [$request]));
    }

    public function testRedirectToReturnsLoginRouteForNonJson()
    {
        $middleware = new AuthenticateStub();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('expectsJson')->andReturn(false);

        $this->assertEquals(route('login'), $this->callProtected($middleware, 'redirectTo', [$request]));
    }

    public function testRedirectToHandlesNullRequest()
    {
        $middleware = new AuthenticateStub();

        // CHECK Middleware method
        // The middleware method is type-hinted to accept a Request. Passing null will
        // result in a TypeError in current PHP versions; assert that behavior so the
        // test matches the runtime contract.
        $this->expectException(\TypeError::class);
        $this->callProtected($middleware, 'redirectTo', [null]);
    }

    public function testRedirectToHandlesEmptyRequest()
    {
        $middleware = new AuthenticateStub();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('expectsJson')->andReturn(null);

        // expectsJson() returning null is falsy, so the middleware should return the
        // login route (same as when expectsJson() returns false)
        $this->assertEquals(route('login'), $this->callProtected($middleware, 'redirectTo', [$request]));
    }

    public function testRedirectToHandlesUnexpectedBehavior()
    {
        $middleware = new AuthenticateStub();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('expectsJson')->andThrow(new \Exception('Unexpected error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unexpected error');

        $this->callProtected($middleware, 'redirectTo', [$request]);
    }

    public function testRedirectToHandlesCustomLogic()
    {
        $middleware = new class extends Authenticate {
            public function __construct() {}
            protected function redirectTo(Request $request): ?string
            {
                return $request->expectsJson() ? null : '/custom-login';
            }
        };
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('expectsJson')->andReturn(false);

        // Subclass returns '/custom-login'
        $this->assertEquals('/custom-login', $this->callProtected($middleware, 'redirectTo', [$request]));
    }

    public function testRedirectToReturnsNullForJsonRequestWithSubclassOverride()
    {
        $middleware = new class extends Authenticate {
            public function __construct() {}
            protected function redirectTo(Request $request): ?string
            {
                return $request->expectsJson() ? null : '/custom-login';
            }
        };
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('expectsJson')->andReturn(true);
        $this->assertNull($this->callProtected($middleware, 'redirectTo', [$request]));
    }

    public function testRedirectToReturnsCustomRouteForNonJsonWithSubclassOverride()
    {
        $middleware = new class extends Authenticate {
            public function __construct() {}
            protected function redirectTo(Request $request): ?string
            {
                return $request->expectsJson() ? null : '/custom-login';
            }
        };
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('expectsJson')->andReturn(false);
        // Define the named route used by the override so route() resolves
        \Illuminate\Support\Facades\Route::get('/custom-login', function () {
            return 'ok';
        })->name('custom.login');
        $this->assertEquals('/custom-login', $this->callProtected($middleware, 'redirectTo', [$request]));
    }

    public function testRedirectToThrowsIfRouteHelperFails()
    {
        $middleware = new AuthenticateStub();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('expectsJson')->andReturn(false);
        // Bind a URL generator mock implementing the UrlGenerator contract so the
        // container type-hint is satisfied and route() will throw as expected.
        $urlMock = Mockery::mock(\Illuminate\Contracts\Routing\UrlGenerator::class);
        $urlMock->shouldReceive('route')->andThrow(new \Exception('Route helper failed'));
        $this->app->instance('url', $urlMock);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Route helper failed');
        $this->callProtected($middleware, 'redirectTo', [$request]);
    }
}
