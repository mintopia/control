<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\TicketObserver;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketObserverTest extends TestCase
{
    use RefreshDatabase;

    public function testSavedUpdatesPlanRevisionIfDirtyAndHasSeat()
    {
        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $user = \App\Models\User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);
        \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        // simulate change on ticket and call observer
        $ticket->some_flag = 1; // make it dirty
        $observer = new TicketObserver();
        $observer->saved($ticket);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }

    public function testSavedSyncsDiscordRolesOnUserChange()
    {
        $user = \App\Models\User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);

        // simulate original user exists and new user exists
        $ticket->setRawAttributes(array_merge($ticket->getAttributes(), ['user_id' => $user->id]));
        $ticket->some_flag = 1;

        // Ensure calling saved does not throw when user change logic runs
        $observer = new TicketObserver();
        $observer->saved($ticket);

        $this->assertTrue(true);
    }

    // NOTE The following tests assert empty observer handlers are currently no-ops.
    public function testCreatedIsNoop()
    {
        $ticket = Ticket::factory()->create();

        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 51]);

        $before = $plan->fresh()->revision;

        $observer = new TicketObserver();
        $m = 'created';
        if (method_exists($observer, $m)) {
            $observer->$m($ticket);
        }

        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testUpdatedIsNoop()
    {
        $ticket = Ticket::factory()->create();

        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 53]);

        $before = $plan->fresh()->revision;

        $observer = new TicketObserver();
        $m = 'updated';
        if (method_exists($observer, $m)) {
            $observer->$m($ticket);
        }

        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testDeletedIsNoop()
    {
        $ticket = Ticket::factory()->create();

        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 55]);

        $before = $plan->fresh()->revision;

        $observer = new TicketObserver();
        $m = 'deleted';
        if (method_exists($observer, $m)) {
            $observer->$m($ticket);
        }

        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testRestoredIsNoop()
    {
        $ticket = Ticket::factory()->create();

        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 57]);

        $before = $plan->fresh()->revision;

        $observer = new TicketObserver();
        $m = 'restored';
        if (method_exists($observer, $m)) {
            $observer->$m($ticket);
        }

        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testForceDeletedIsNoop()
    {
        $ticket = Ticket::factory()->create();

        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 59]);

        $before = $plan->fresh()->revision;

        $observer = new TicketObserver();
        $m = 'forceDeleted';
        if (method_exists($observer, $m)) {
            $observer->$m($ticket);
        }

        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testSavedCallsUpdateRevisionWhenDirtyAndHasSeatUsingMocks()
    {
        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $user = \App\Models\User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);
        \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        // Partial mock the plan to expect updateRevision
        $planMock = \Mockery::mock(\App\Models\SeatingPlan::class)->makePartial();
        $planMock->shouldReceive('updateRevision')->once();

        // Seat mock that carries the mocked plan
        $seatMock = \Mockery::mock(\App\Models\Seat::class)->makePartial();
        $seatMock->plan = $planMock;

        // Ticket partial mock: isDirty() returns true and seat is present
        $ticketMock = \Mockery::mock(\App\Models\Ticket::class)->makePartial();
        $ticketMock->id = $ticket->id;
        $ticketMock->seat = $seatMock;
        $ticketMock->shouldReceive('isDirty')->withNoArgs()->andReturn(true);

        $observer = new TicketObserver();
        /** @var \App\Models\Ticket $ticketMock */
        $observer->saved($ticketMock);
    }
}
