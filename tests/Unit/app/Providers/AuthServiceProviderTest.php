<?php

namespace Tests\Unit\app\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use Mockery;

class AuthServiceProviderTest extends TestCase
{
    public function testGatesAreDefined()
    {
        Gate::shouldReceive('define')->with('admin', \Closure::class)->once();
        Gate::shouldReceive('define')->with('viewPulse', \Closure::class)->once();
        Gate::shouldReceive('define')->with('manager', \Closure::class)->once();
        Gate::shouldReceive('define')->with('anyPrivilegedRole', \Closure::class)->once();
        $provider = new \app\Providers\AuthServiceProvider(app());
        $provider->boot();
    }
}
