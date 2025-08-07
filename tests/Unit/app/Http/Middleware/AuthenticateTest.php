<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\Authenticate;
use Illuminate\Http\Request;

class AuthenticateStub extends Authenticate
{
    public function __construct() {}
}

class AuthenticateTest extends TestCase
{
    public function testRedirectToReturnsNullForJson()
    {
        $middleware = new AuthenticateStub();
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('expectsJson')->willReturn(true);
        $this->assertNull($this->callProtected($middleware, 'redirectTo', [$request]));
    }

    public function testRedirectToReturnsLoginRouteForNonJson()
    {
        $middleware = new AuthenticateStub();
        $request = $this->createMock(Request::class);
        $request->expects($this->any())->method('expectsJson')->willReturn(false);
        $this->fail('Static method mocking for route() or Route facade is not supported in this environment.');
    }

    public function testRedirectToFailsForStaticRoute()
    {
        $this->fail('Static method mocking for route() or Route facade is not supported in this environment.');
    }

    private function callProtected($object, $method, $args = [])
    {
        $ref = new \ReflectionClass($object);
        $meth = $ref->getMethod($method);
        $meth->setAccessible(true);
        return $meth->invokeArgs($object, $args);
    }
}
