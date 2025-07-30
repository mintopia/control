<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\Authenticate;
use Illuminate\Http\Request;

class AuthenticateTest extends TestCase
{
    public function testRedirectToReturnsNullForJson()
    {
        $middleware = new Authenticate();
        $request = $this->createMock(Request::class);
        $request->method('expectsJson')->willReturn(true);
        $this->assertNull($this->callProtected($middleware, 'redirectTo', [$request]));
    }

    public function testRedirectToReturnsLoginRouteForNonJson()
    {
        $middleware = new Authenticate();
        $request = $this->createMock(Request::class);
        $request->method('expectsJson')->willReturn(false);
        \Illuminate\Support\Facades\Route::shouldReceive('login')->andReturn('/login');
        $this->assertNotNull($this->callProtected($middleware, 'redirectTo', [$request]));
    }

    private function callProtected($object, $method, $args = [])
    {
        $ref = new \ReflectionClass($object);
        $meth = $ref->getMethod($method);
        $meth->setAccessible(true);
        return $meth->invokeArgs($object, $args);
    }
}
