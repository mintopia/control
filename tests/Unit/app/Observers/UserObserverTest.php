<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\UserObserver;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserObserverTest extends TestCase
{
    use RefreshDatabase;

    public function testCreatedAssignsAdminRoleToFirstUser()
    {
        // Ensure the admin role exists (use forceFill to avoid mass-assignment guards in tests)
        $adminRole = (new Role())->forceFill(['code' => 'admin', 'name' => 'Administrator']);
        $adminRole->save();

        // At this point there are no users in the DB. Create the first user.
        $user = User::factory()->create();

        // Run the observer directly
        $observer = new UserObserver();
        $observer->created($user);

        // Assert the user now has the admin role attached
        $this->assertTrue($user->roles()->whereCode('admin')->exists());
    }
}
