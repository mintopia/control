<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\RedirectOnFirstLoginMiddleware;

class RedirectOnFirstLoginMiddlewareStub extends RedirectOnFirstLoginMiddleware {}

class RedirectOnFirstLoginMiddlewareTest extends TestCase
{
    public function testCanInstantiateRedirectOnFirstLoginMiddleware()
    {
        $middleware = new RedirectOnFirstLoginMiddlewareStub();
        $this->assertInstanceOf(RedirectOnFirstLoginMiddleware::class, $middleware);
    }

    public function testHandleRedirectsIfFirstLogin()
    {
        $middleware = new RedirectOnFirstLoginMiddlewareStub();
        $mockUser = (object)['first_login' => true];
        $mockRequest = $this->getMockBuilder(\Illuminate\Http\Request::class)
            ->onlyMethods(['user'])
            ->getMock();
        $mockRequest->expects($this->any())->method('user')->willReturn($mockUser);
        $next = function () {
            $this->fail('Next middleware should not be called if first_login is true');
        };
        $response = $middleware->handle($mockRequest, $next);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    public function testHandleCallsNextIfNotFirstLogin()
    {
        $middleware = new RedirectOnFirstLoginMiddlewareStub();
        $mockUser = (object)['first_login' => false];
        $mockRequest = $this->getMockBuilder(\Illuminate\Http\Request::class)
            ->onlyMethods(['user'])
            ->getMock();
        $mockRequest->expects($this->any())->method('user')->willReturn($mockUser);
        $called = false;
        $next = function () use (&$called) {
            $called = true;
            return new \Illuminate\Http\Response('next-called');
        };
        $result = $middleware->handle($mockRequest, $next);
        $this->assertTrue($called, 'Next middleware was not called');
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $result);
        $this->assertEquals('next-called', $result->getContent());
    }

    public function testHandleWithNoUserDoesNotRedirect()
    {
        $middleware = new RedirectOnFirstLoginMiddlewareStub();
        $mockRequest = $this->getMockBuilder(\Illuminate\Http\Request::class)
            ->onlyMethods(['user'])
            ->getMock();
        $mockRequest->expects($this->any())->method('user')->willReturn(null);
        $called = false;
        $next = function ($request) use (&$called) {
            $called = true;
            return new \Illuminate\Http\Response('next-called');
        };
        $result = $middleware->handle($mockRequest, $next);
        $this->assertTrue($called, 'Next middleware was not called');
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $result);
        $this->assertEquals('next-called', $result->getContent());
    }

    public function testHandleWithUserWithoutFirstLoginPropertyDoesNotRedirect()
    {
        $middleware = new RedirectOnFirstLoginMiddlewareStub();
        $mockUser = (object)[];
        $mockRequest = $this->getMockBuilder(\Illuminate\Http\Request::class)
            ->onlyMethods(['user'])
            ->getMock();
        $mockRequest->expects($this->any())->method('user')->willReturn($mockUser);
        $called = false;
        $next = function ($request) use (&$called) {
            $called = true;
            return new \Illuminate\Http\Response('next-called');
        };
        $result = $middleware->handle($mockRequest, $next);
        $this->assertTrue($called, 'Next middleware was not called');
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $result);
        $this->assertEquals('next-called', $result->getContent());
    }
}
