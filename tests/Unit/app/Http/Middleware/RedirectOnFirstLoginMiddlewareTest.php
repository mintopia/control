<?php

namespace Tests\Unit\app\Http\Middleware;

use App\Http\Middleware\RedirectOnFirstLoginMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class RedirectOnFirstLoginMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /** @var RedirectOnFirstLoginMiddleware */
    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        // create an anonymous subclass instance to avoid relying on helper files
        $this->middleware = new class () extends RedirectOnFirstLoginMiddleware {
        };
    }

    public function testCanInstantiateRedirectOnFirstLoginMiddleware()
    {
        $this->assertInstanceOf(RedirectOnFirstLoginMiddleware::class, $this->middleware);
    }

    public function testHandleRedirectsIfFirstLogin()
    {
        $middleware = $this->middleware;
        $mockUser = (object)['first_login' => true];
        $request = Request::create('/', 'GET');
        // Use a user resolver to provide the mocked user without mocking Request methods
        $request->setUserResolver(function () use ($mockUser) {
            return $mockUser;
        });
        $next = function () {
            $this->fail('Next middleware should not be called if first_login is true');
        };
        $response = $middleware->handle($request, $next);
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testHandleCallsNextIfNotFirstLogin()
    {
        $middleware = $this->middleware;
        $mockUser = (object)['first_login' => false];
        $request = Request::create('/', 'GET');
        $request->setUserResolver(function () use ($mockUser) {
            return $mockUser;
        });
        $called = false;
        $next = function () use (&$called) {
            $called = true;
            return new Response('next-called');
        };
        $result = $middleware->handle($request, $next);
        $this->assertTrue($called, 'Next middleware was not called');
        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals('next-called', $result->getContent());
    }

    public function testHandleWithNoUserDoesNotRedirect()
    {
        $middleware = $this->middleware;
        $request = Request::create('/', 'GET');
        $request->setUserResolver(function () {
            return null;
        });
        $called = false;
        $next = function ($request) use (&$called) {
            $called = true;
            return new Response('next-called');
        };
        $result = $middleware->handle($request, $next);
        $this->assertTrue($called, 'Next middleware was not called');
        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals('next-called', $result->getContent());
    }

    public function testHandleWithUserWithoutFirstLoginPropertyDoesNotRedirect()
    {
        $middleware = $this->middleware;
        $mockUser = (object)[];
        $request = Request::create('/', 'GET');
        $request->setUserResolver(function () use ($mockUser) {
            return $mockUser;
        });
        $called = false;
        $next = function ($request) use (&$called) {
            $called = true;
            return new Response('next-called');
        };
        $result = $middleware->handle($request, $next);
        $this->assertTrue($called, 'Next middleware was not called');
        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals('next-called', $result->getContent());
    }
}
