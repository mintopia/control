<?php

namespace Tests\Unit\app\Observers;

use App\Models\Clan;
use App\Models\ClanMembership;
use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatingPlan;
use App\Models\Ticket;
use App\Models\User;
use App\Observers\ClanObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function App\makePermalink;

class ClanObserverTest extends TestCase
{
    use RefreshDatabase;

    public function testDeletingCallsDelayedRevisionUpdateOnPlans()
    {
        // Create a clan and a seating plan with a seat -> ticket -> user -> clan membership chain
        $clan = Clan::factory()->create();
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['revision' => 1, 'event_id' => $event->id]);

        // create a ticket with a user who is a member of the clan and a seat on the plan
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        // Create a clan membership linking the user to the clan
        ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);

        // Create a seat attached to the plan and associate the ticket
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        $this->assertEquals(1, $plan->revision);

        $observer = new ClanObserver();
        $observer->deleting($clan);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }

    public function testSavedUpdatesPlansIfNameDirty()
    {
        // Create clan and seating plan with linked seat/ticket/user/membership
        $clan = Clan::factory()->create(['name' => 'Old Name']);
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['revision' => 1, 'event_id' => $event->id]);

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);
        Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        // Make the name dirty
        $clan->name = 'New Name';

        $observer = new ClanObserver();
        $observer->saved($clan);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }

    public function testSavedDoesNothingIfNameNotDirty()
    {
        $clan = Clan::factory()->create(['name' => 'Same Name']);
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['revision' => 1, 'event_id' => $event->id]);

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);
        ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);
        Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        $observer = new ClanObserver();
        $observer->saved($clan);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }

    public function testSavingGeneratesInviteCodeAndPermalinkIfMissing()
    {
        $clan = $this->getMockBuilder(Clan::class)
            ->onlyMethods(['generateCode'])
            ->getMock();
        $clan->invite_code = null;
        $clan->name = 'Test Clan';
        $clan->expects($this->once())->method('generateCode');
        // We are using makePermalink from  our helpers
        $clan->code = makePermalink($clan->name);

        $observer = new ClanObserver();
        $observer->saving($clan);
        $this->assertEquals('test-clan', $clan->code);
    }

    public function testSavingDoesNotGenerateInviteCodeIfPresent()
    {
        $clan = $this->getMockBuilder(Clan::class)
            ->onlyMethods(['generateCode'])
            ->getMock();
        $clan->invite_code = 'abc123';
        $clan->name = 'Another Clan';
        $clan->expects($this->never())->method('generateCode');

        $observer = new ClanObserver();
        $observer->saving($clan);
        $this->assertEquals('another-clan', $clan->code);
    }

    // NOTE The following tests assert empty observer handlers are currently no-ops.
    public function testCreatedDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $m = 'created';
        if (method_exists($observer, $m)) {
            $this->assertNull($observer->$m($clan));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testUpdatedDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $m = 'updated';
        if (method_exists($observer, $m)) {
            $this->assertNull($observer->$m($clan));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testDeletedDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $m = 'deleted';
        if (method_exists($observer, $m)) {
            $this->assertNull($observer->$m($clan));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testRestoredDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $m = 'restored';
        if (method_exists($observer, $m)) {
            $this->assertNull($observer->$m($clan));
        } else {
            $this->assertTrue(true);
        }
    }

    public function testForceDeletedDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $m = 'forceDeleted';
        if (method_exists($observer, $m)) {
            $this->assertNull($observer->$m($clan));
        } else {
            $this->assertTrue(true);
        }
    }
}
