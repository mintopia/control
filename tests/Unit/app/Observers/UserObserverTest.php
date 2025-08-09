<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\UserObserver;
use App\Models\User;
use App\Models\Role;

class UserObserverTest extends TestCase
{
    // FIXME Class already exists
//     public function testCreatedAssignsAdminRoleToFirstUser()
//     {
//         // Mock static methods on Role
//         \Mockery::mock('overload:App\Models\Role')
//             ->shouldReceive('whereCode')->with('admin')->andReturnSelf()
//             ->shouldReceive('first')->andReturn(new Role());

//         // Mock static method on User
//         \Mockery::mock('overload:App\Models\User')
//             ->shouldReceive('count')->andReturn(1);

//         // Create a real User instance and mock only the roles() relation
//         $user = new User();
//         $user->id = 1;

//         $rolesRelation = \Mockery::mock();
//         $rolesRelation->shouldReceive('attach')->once();

//         // Override the roles() method to return the mock
//         $user->setRelation('roles', $rolesRelation);

//         $observer = new UserObserver();
//         $observer->created($user);
//     }
// }
