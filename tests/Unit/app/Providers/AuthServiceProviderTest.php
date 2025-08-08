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

    public function testAdminGateLogic()
    {
        $provider = new \App\Providers\AuthServiceProvider(app());
        $provider->boot();

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasRole'])
            ->getMock();
        $user->expects($this->atLeastOnce())
            ->method('hasRole')
            ->with('admin')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue(Gate::forUser($user)->allows('admin'));
        $this->assertFalse(Gate::forUser($user)->allows('admin'));
    }

    public function testManagerGateLogic()
    {
        $provider = new \App\Providers\AuthServiceProvider(app());
        $provider->boot();

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasRole'])
            ->getMock();
        $user->expects($this->atLeastOnce())
            ->method('hasRole')
            ->with('manager')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue(Gate::forUser($user)->allows('manager'));
        $this->assertFalse(Gate::forUser($user)->allows('manager'));
    }

    public function testAnyPrivilegedRoleGateLogic()
    {
        $provider = new \App\Providers\AuthServiceProvider(app());
        $provider->boot();

        // manager true, admin false
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasRole'])
            ->getMock();
        $user->method('hasRole')->willReturnCallback(function ($role) {
            return $role === 'manager';
        });
        $this->assertTrue(Gate::forUser($user)->allows('anyPrivilegedRole'));

        // manager false, admin true
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasRole'])
            ->getMock();
        $user->method('hasRole')->willReturnCallback(function ($role) {
            return $role === 'admin';
        });
        $this->assertTrue(Gate::forUser($user)->allows('anyPrivilegedRole'));

        // manager false, admin false
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasRole'])
            ->getMock();
        $user->method('hasRole')->willReturn(false);
        $this->assertFalse(Gate::forUser($user)->allows('anyPrivilegedRole'));
    }

    public function testViewPulseGateLogic()
    {
        $provider = new \App\Providers\AuthServiceProvider(app());
        $provider->boot();

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasRole'])
            ->getMock();
        $user->expects($this->atLeastOnce())
            ->method('hasRole')
            ->with('admin')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue(Gate::forUser($user)->allows('viewPulse'));
        $this->assertFalse(Gate::forUser($user)->allows('viewPulse'));
    }
}
