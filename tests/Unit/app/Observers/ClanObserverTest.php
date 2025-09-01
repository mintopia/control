<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\ClanObserver;
use App\Models\Clan;
use App\Models\SeatingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function App\makePermalink;

class ClanObserverTest extends TestCase
{
    use RefreshDatabase;

    public function testDeletingCallsDelayedRevisionUpdateOnPlans()
    {
        // Create a clan and a seating plan with a seat -> ticket -> user -> clan membership chain
        $clan = Clan::factory()->create();
        $event = \App\Models\Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['revision' => 1, 'event_id' => $event->id]);

        // create a ticket with a user who is a member of the clan and a seat on the plan
        $user = \App\Models\User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);
        // Create a clan membership linking the user to the clan
        \App\Models\ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);

        // Create a seat attached to the plan and associate the ticket
        $seat = \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        $this->assertEquals(1, $plan->revision);

        $observer = new \App\Observers\ClanObserver();
        $observer->deleting($clan);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }

    public function testSavedUpdatesPlansIfNameDirty()
    {
        // Create clan and seating plan with linked seat/ticket/user/membership
        $clan = \App\Models\Clan::factory()->create(['name' => 'Old Name']);
        $event = \App\Models\Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['revision' => 1, 'event_id' => $event->id]);

        $user = \App\Models\User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);
        \App\Models\ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);
        \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        // Make the name dirty
        $clan->name = 'New Name';

        $observer = new \App\Observers\ClanObserver();
        $observer->saved($clan);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }

    public function testSavedDoesNothingIfNameNotDirty()
    {
        $clan = \App\Models\Clan::factory()->create(['name' => 'Same Name']);
        $event = \App\Models\Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['revision' => 1, 'event_id' => $event->id]);

        $user = \App\Models\User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);
        \App\Models\ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user->id]);
        \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        $observer = new \App\Observers\ClanObserver();
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

    public function testCreatedDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $this->assertNull($observer->created($clan));
    }

    public function testUpdatedDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $this->assertNull($observer->updated($clan));
    }

    public function testDeletedDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $this->assertNull($observer->deleted($clan));
    }

    public function testRestoredDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $this->assertNull($observer->restored($clan));
    }

    public function testForceDeletedDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $this->assertNull($observer->forceDeleted($clan));
    }
}
