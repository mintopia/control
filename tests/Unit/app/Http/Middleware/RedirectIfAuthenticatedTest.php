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

    // public function testRedirectsAuthenticatedUser()
    // {
    //     $request = Mockery::mock(Request::class);
    //     $guard = Mockery::mock(Guard::class);

    //     $guard->shouldReceive('check')->once()->andReturn(true);

    //     $middleware = new RedirectIfAuthenticatedStub($guard);

    //     $response = $middleware->handle($request, function () {}, 'web');

    //     $this->assertInstanceOf(RedirectResponse::class, $response);
    //     $this->assertEquals('/home', $response->headers->get('Location'));
    // }

    // public function testAllowsUnauthenticatedUser()
    // {
    //     $request = Mockery::mock(Request::class);
    //     $guard = Mockery::mock(Guard::class);

    //     $guard->shouldReceive('check')->once()->andReturn(false);

    //     $middleware = new RedirectIfAuthenticatedStub($guard);

    //     $response = $middleware->handle($request, function () {
    //         return 'next';
    //     }, 'web');

    //     $this->assertEquals('next', $response);
    // }
}
