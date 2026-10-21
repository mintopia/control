<?php

namespace Tests\Unit\app\Observers;

use App\Models\Clan;
use App\Models\ClanMembership;
use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatingPlan;
use App\Models\Ticket;
use App\Models\User;
use App\Observers\ClanMembershipObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ClanMembershipObserverTest extends TestCase
{
    use RefreshDatabase;

    public function testDeletingCallsDelayedRevisionUpdateOnPlans()
    {
        // Build a scenario: seating plan with seat->ticket->user->clanMembership to trigger whereHas
        $clan = Clan::factory()->create();
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $membership = ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);
        Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        $observer = new ClanMembershipObserver();
        $observer->deleting($membership);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }

    public function testSavedUpdatesPlansIfUserIdDirty()
    {
        $clan = Clan::factory()->create();
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $membership = ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);
        Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        // Simulate the model being dirty for user_id by using an anonymous subclass
        $membership->isDirty = fn($attr = null) => $attr === 'user_id' || (is_array($attr) && in_array('user_id', $attr));

        $observer = new ClanMembershipObserver();
        $observer->saved($membership);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }

    public function testSavedUpdatesRevisionWhenUserIdDirtyUsingMock()
    {
        $clan = Clan::factory()->create();
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $membership = ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);
        Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        // Create a partial mock that returns true for isDirty('user_id') but retains the real id
        $membershipMock = Mockery::mock(ClanMembership::class)->makePartial();
        $membershipMock->id = $membership->id;
        $membershipMock->shouldReceive('isDirty')->with('user_id')->andReturn(true);

        $observer = new ClanMembershipObserver();
        $observer->saved($membershipMock);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }

    public function testSavedDoesNothingIfUserIdNotDirty()
    {
        $clan = Clan::factory()->create();
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        $membership = ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);
        Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        // Not dirty
        $membership->isDirty = fn($attr = null) => false;

        $observer = new ClanMembershipObserver();
        $observer->saved($membership);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }
}
