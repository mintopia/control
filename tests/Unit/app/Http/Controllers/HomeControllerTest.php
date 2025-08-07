<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use Mockery;

class HomeControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new HomeController();
        $this->assertInstanceOf(HomeController::class, $controller);
    }

    public function testHomeReturnsView()
    {
        $user = \Mockery::mock();
        $user->shouldReceive('tickets')->andReturnSelf();
        $user->shouldReceive('whereHas')->andReturnSelf();
        $user->shouldReceive('with')->andReturnSelf();
        $user->shouldReceive('paginate')->andReturn([]);
        $user->shouldReceive('hasRole')->andReturn(true);
        $request = new Request();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $controller = new HomeController();
        $response = $controller->home($request);
        $this->assertTrue(is_object($response));
    }
}
