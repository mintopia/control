<?php

namespace Tests\Unit\App\Providers;

use Tests\TestCase;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Support\Facades\Facade;
use Laravel\Telescope\Telescope;

class TelescopeServiceProviderTest extends TestCase
{

    public function testRegisterConfiguresTelescope()
    {
        // Facade being used does not implement the required getFacadeAccessor method?

        Facade::shouldReceive('getFacadeApplication')->andReturn(app());
        Facade::clearResolvedInstance('telescope');
        $telescopeMock = \Mockery::mock('overload:Laravel\Telescope\Telescope');
        $telescopeMock->shouldReceive('night')->once();
        $telescopeMock->shouldReceive('filter')->once();
        $telescopeMock->shouldReceive('avatar')->once();
        $provider = new \App\Providers\TelescopeServiceProvider(app());
        $provider->register();
        $this->assertTrue(true);
    }

    public function testHideSensitiveRequestDetails()
    {
        $telescopeMock = \Mockery::mock('overload:Laravel\Telescope\Telescope');
        $telescopeMock->shouldReceive('hideRequestParameters')->once();
        $telescopeMock->shouldReceive('hideRequestHeaders')->once();
        $provider = new \App\Providers\TelescopeServiceProvider(app());
        $this->invokeProtected($provider, 'hideSensitiveRequestDetails');
        $this->assertTrue(true);
    }

    public function testGateDefinesViewTelescope()
    {
        Gate::shouldReceive('define')->with('viewTelescope', \Closure::class)->once();
        $provider = new \App\Providers\TelescopeServiceProvider(app());
        $this->invokeProtected($provider, 'gate');
        $this->assertTrue(true);
    }

    private function invokeProtected($object, $method, $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}
