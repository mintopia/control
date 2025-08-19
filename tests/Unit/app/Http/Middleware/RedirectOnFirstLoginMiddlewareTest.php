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
        $request = \Illuminate\Http\Request::create('/', 'GET');
        // Use a user resolver to provide the mocked user without mocking Request methods
        $request->setUserResolver(function () use ($mockUser) {
            return $mockUser;
        });
        $next = function () {
            $this->fail('Next middleware should not be called if first_login is true');
        };
        $response = $middleware->handle($request, $next);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    public function testHandleCallsNextIfNotFirstLogin()
    {
        $middleware = new RedirectOnFirstLoginMiddlewareStub();
        $mockUser = (object)['first_login' => false];
        $request = \Illuminate\Http\Request::create('/', 'GET');
        $request->setUserResolver(function () use ($mockUser) {
            return $mockUser;
        });
        $called = false;
        $next = function () use (&$called) {
            $called = true;
            return new \Illuminate\Http\Response('next-called');
        };
        $result = $middleware->handle($request, $next);
        $this->assertTrue($called, 'Next middleware was not called');
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $result);
        $this->assertEquals('next-called', $result->getContent());
    }

    public function testHandleWithNoUserDoesNotRedirect()
    {
        $middleware = new RedirectOnFirstLoginMiddlewareStub();
        $request = \Illuminate\Http\Request::create('/', 'GET');
        $request->setUserResolver(function () {
            return null;
        });
        $called = false;
        $next = function ($request) use (&$called) {
            $called = true;
            return new \Illuminate\Http\Response('next-called');
        };
        $result = $middleware->handle($request, $next);
        $this->assertTrue($called, 'Next middleware was not called');
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $result);
        $this->assertEquals('next-called', $result->getContent());
    }

    public function testHandleWithUserWithoutFirstLoginPropertyDoesNotRedirect()
    {
        $middleware = new RedirectOnFirstLoginMiddlewareStub();
        $mockUser = (object)[];
        $request = \Illuminate\Http\Request::create('/', 'GET');
        $request->setUserResolver(function () use ($mockUser) {
            return $mockUser;
        });
        $called = false;
        $next = function ($request) use (&$called) {
            $called = true;
            return new \Illuminate\Http\Response('next-called');
        };
        $result = $middleware->handle($request, $next);
        $this->assertTrue($called, 'Next middleware was not called');
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $result);
        $this->assertEquals('next-called', $result->getContent());
    }
}
