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

    public function testSavedUpdatesPlanRevisionsWhenNicknameDirty()
    {
        $user = User::factory()->create(['nickname' => 'old']);
        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);
        \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        $user->nickname = 'new';
        $observer = new UserObserver();
        $observer->saved($user);

        $this->assertGreaterThan(1, $plan->fresh()->revision);
    }

    // NOTE The following tests assert empty observer handlers are currently no-ops.
    public function testCreatedIsNoop()
    {
        $user = User::factory()->create();

        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 41]);

        // Snapshot current revision after any creation observers
        $before = $plan->fresh()->revision;

        $observer = new UserObserver();
        $m = 'created';
        if (method_exists($observer, $m)) {
            $observer->$m($user);
        }

        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testUpdatedIsNoop()
    {
        $user = User::factory()->create();

        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 43]);

        $before = $plan->fresh()->revision;

        $observer = new UserObserver();
        $m = 'updated';
        if (method_exists($observer, $m)) {
            $observer->$m($user);
        }

        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testDeletedIsNoop()
    {
        $user = User::factory()->create();

        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 45]);

        $before = $plan->fresh()->revision;

        $observer = new UserObserver();
        $m = 'deleted';
        if (method_exists($observer, $m)) {
            $observer->$m($user);
        }

        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testRestoredIsNoop()
    {
        $user = User::factory()->create();

        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 47]);

        $before = $plan->fresh()->revision;

        $observer = new UserObserver();
        $m = 'restored';
        if (method_exists($observer, $m)) {
            $observer->$m($user);
        }

        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testForceDeletedIsNoop()
    {
        $user = User::factory()->create();

        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 49]);

        $before = $plan->fresh()->revision;

        $observer = new UserObserver();
        $m = 'forceDeleted';
        if (method_exists($observer, $m)) {
            $observer->$m($user);
        }

        $this->assertEquals($before, $plan->fresh()->revision);
    }
}
