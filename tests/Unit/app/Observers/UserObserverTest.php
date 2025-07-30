<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\UserObserver;
use App\Models\User;
use App\Models\Role;

class UserObserverTest extends TestCase
{
    public function testCreatedAssignsAdminRoleToFirstUser()
    {
        $user = $this->getMockBuilder(User::class)->onlyMethods(['save', 'roles'])->getMock();
        $user->expects($this->once())->method('save');
        $user->method('roles')->willReturnSelf();
        $user->id = 1;
        $role = $this->getMockBuilder(Role::class)->disableOriginalConstructor()->getMock();
        Role::shouldReceive('whereCode')->with('admin')->andReturnSelf();
        Role::shouldReceive('first')->andReturn($role);
        User::shouldReceive('count')->andReturn(1);
        $user->expects($this->once())->method('roles')->willReturnSelf();
        $user->expects($this->once())->method('save');
        $observer = new UserObserver();
        $observer->created($user);
    }
}
