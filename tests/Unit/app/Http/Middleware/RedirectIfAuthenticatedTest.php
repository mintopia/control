<?php

namespace Tests\Unit\app\Http\Middleware;

use Mockery;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use Tests\TestCase;
use App\Http\Middleware\RedirectIfAuthenticated;

class RedirectIfAuthenticatedStub extends RedirectIfAuthenticated
{
    protected $guard;

    public function __construct($guard = null)
    {
        $this->guard = $guard;
    }
}

class RedirectIfAuthenticatedTest extends TestCase
{
    public function testCanInstantiateRedirectIfAuthenticated()
    {
        $middleware = new RedirectIfAuthenticatedStub();
        $this->assertInstanceOf(RedirectIfAuthenticated::class, $middleware);
    }

    public function testRedirectsAuthenticatedUser()
    {
        // Mock the Auth facade guard to return a guard whose check() returns true
        $guard = Mockery::mock();
        $guard->shouldReceive('check')->once()->andReturnTrue();

        // Bind a stub Auth facade replacement via container so Auth::guard() resolves
        \Illuminate\Support\Facades\Auth::shouldReceive('guard')->andReturn($guard);

        $middleware = new RedirectIfAuthenticatedStub();
        $request = Request::create('/');

        $response = $middleware->handle($request, function () {
            return 'next-called';
        });

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    public function testAllowsUnauthenticatedUser()
    {
        $guard = Mockery::mock();
        $guard->shouldReceive('check')->once()->andReturnFalse();

        \Illuminate\Support\Facades\Auth::shouldReceive('guard')->andReturn($guard);

        $middleware = new RedirectIfAuthenticatedStub();
        $request = Request::create('/');

        $response = $middleware->handle($request, function ($req) {
            return new \Symfony\Component\HttpFoundation\Response('ok', 200);
        });

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
