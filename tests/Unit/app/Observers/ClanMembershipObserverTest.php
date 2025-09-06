<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\ClanMembershipObserver;
use App\Models\ClanMembership;
use App\Models\SeatingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClanMembershipObserverTest extends TestCase
{
    use RefreshDatabase;
    public function testDeletingCallsDelayedRevisionUpdateOnPlans()
    {
        // Build a scenario: seating plan with seat->ticket->user->clanMembership to trigger whereHas
        $clan = \App\Models\Clan::factory()->create();
        $event = \App\Models\Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $user = \App\Models\User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);
        $membership = \App\Models\ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);
        \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        $observer = new ClanMembershipObserver();
        $observer->deleting($membership);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }

    public function testSavedUpdatesPlansIfUserIdDirty()
    {
        $clan = \App\Models\Clan::factory()->create();
        $event = \App\Models\Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $user = \App\Models\User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);
        $membership = \App\Models\ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);
        \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        // Simulate the model being dirty for user_id by using an anonymous subclass
        $membership->isDirty = fn($attr = null) => $attr === 'user_id' || (is_array($attr) && in_array('user_id', $attr));

        $observer = new ClanMembershipObserver();
        $observer->saved($membership);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }

    public function testSavedUpdatesRevisionWhenUserIdDirtyUsingMock()
    {
        $clan = \App\Models\Clan::factory()->create();
        $event = \App\Models\Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $user = \App\Models\User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);
        $membership = \App\Models\ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);
        \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        // Create a partial mock that returns true for isDirty('user_id') but retains the real id
        $membershipMock = \Mockery::mock(\App\Models\ClanMembership::class)->makePartial();
        $membershipMock->id = $membership->id;
        $membershipMock->shouldReceive('isDirty')->with('user_id')->andReturn(true);

        $observer = new ClanMembershipObserver();
        $observer->saved($membershipMock);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }

    public function testSavedDoesNothingIfUserIdNotDirty()
    {
        $clan = \App\Models\Clan::factory()->create();
        $event = \App\Models\Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $user = \App\Models\User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);
        $membership = \App\Models\ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);
        \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        // Not dirty
        $membership->isDirty = fn($attr = null) => false;

        $observer = new ClanMembershipObserver();
        $observer->saved($membership);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }
}
