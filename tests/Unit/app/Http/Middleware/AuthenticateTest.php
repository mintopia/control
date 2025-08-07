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

    // FIX Null, Empty cannot be tested as Request does not allow NULL - Is it needed?

    // public function testRedirectToHandlesNullRequest()
    // {
    //     $middleware = new AuthenticateStub();

    //     $this->assertNull($this->callProtected($middleware, 'redirectTo', [null]));
    // }

    // public function testRedirectToHandlesEmptyRequest()
    // {
    //     $middleware = new AuthenticateStub();
    //     $request = Mockery::mock(Request::class);
    //     $request->shouldReceive('expectsJson')->andReturn(null);

    //     $this->assertNull($this->callProtected($middleware, 'redirectTo', [$request]));
    // }

    public function testRedirectToHandlesUnexpectedBehavior()
    {
        $middleware = new AuthenticateStub();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('expectsJson')->andThrow(new \Exception('Unexpected error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unexpected error');

        $this->callProtected($middleware, 'redirectTo', [$request]);
    }

    /* FIXME route named 'custom.login' is not defined in your application, need to define this route in routes file?
    <?php

    use Illuminate\Support\Facades\Route;

    // Existing routes...

    // Added for AuthenticateTest::testRedirectToHandlesCustomLogic
    Route::get('/custom-login', function () {
        // You can return a view or just a string for testing
        return 'Custom Login Page';
    })->name('custom.login');
    */

    public function testRedirectToHandlesCustomLogic()
    {
        $middleware = new AuthenticateStub();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('expectsJson')->andReturn(false);

        // Simulate a custom route for testing
        $customRoute = 'custom.login';
        $this->assertEquals(route($customRoute), $this->callProtected($middleware, 'redirectTo', [$request]));
    }

    private function callProtected($object, $method, $args = [])
    {
        $ref = new \ReflectionClass($object);
        $meth = $ref->getMethod($method);
        $meth->setAccessible(true);
        return $meth->invokeArgs($object, $args);
    }
}
