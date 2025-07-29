<?php

namespace Tests\Unit\App\Providers;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Tests\TestCase;

class RouteServiceProviderTest extends TestCase
{
    public function testBootRegistersRateLimiterAndRoutes()
    {
        RateLimiter::shouldReceive('for')->once()->andReturnUsing(function ($name, $callback) {
            $request = new \Illuminate\Http\Request;
            $callback($request);
        });
        Route::shouldReceive('middleware')->with('api')->andReturnSelf();
        Route::shouldReceive('prefix')->with('api')->andReturnSelf();
        Route::shouldReceive('group')->with(base_path('routes/api.php'))->andReturnSelf();
        Route::shouldReceive('middleware')->with('web')->andReturnSelf();
        Route::shouldReceive('group')->with(base_path('routes/web.php'))->andReturnSelf();
        $provider = new \App\Providers\RouteServiceProvider(app());
        $provider->boot();
        $this->assertTrue(true);
    }
}
