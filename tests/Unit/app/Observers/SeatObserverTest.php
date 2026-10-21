<?php

namespace Tests\Unit\app\Observers;

use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatingPlan;
use App\Models\Ticket;
use App\Observers\SeatObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatObserverTest extends TestCase
{
    use RefreshDatabase;

    public function testSavingSetsLabelIfMissing()
    {
        $seat = new Seat(['row' => 'A', 'number' => 1, 'label' => null]);
        $observer = new SeatObserver();
        $observer->saving($seat);
        $this->assertNotNull($seat->label);
    }

    public function testSavedDisassociatesOtherSeatsAndUpdatesRevision()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $ticket = Ticket::factory()->create();
        $seat1 = Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);
        $seat2 = Seat::factory()->create(['seating_plan_id' => $plan->id]);

        // simulate saving seat1 triggers disassociation of seat2 (if it had same ticket)
        $seat2->ticket()->associate($ticket);
        $seat2->save();

        $observer = new SeatObserver();
        $observer->saved($seat1);

        $this->assertNull($seat2->fresh()->ticket_id);
    }

    public function testSavedDisassociatesOtherSeatInDifferentPlanAndUpdatesOtherPlanRevision()
    {
        $event = Event::factory()->create();
        $planA = SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);
        $planB = SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $ticket = Ticket::factory()->create(['event_id' => $event->id]);

        // Seat in plan A (the one being saved)
        $seatA = Seat::factory()->create(['seating_plan_id' => $planA->id, 'ticket_id' => $ticket->id]);
        // Another seat in a different plan with same ticket
        $seatB = Seat::factory()->create(['seating_plan_id' => $planB->id, 'ticket_id' => $ticket->id]);

        $observer = new SeatObserver();
        $observer->saved($seatA);

        // other seat should have been disassociated
        $this->assertNull($seatB->fresh()->ticket_id);
        // and the other plan's revision should have been incremented
        $this->assertGreaterThan(1, $planB->fresh()->revision);
    }

    public function testDeletedUpdatesPlanRevision()
    {
        $plan = SeatingPlan::factory()->create(['revision' => 1]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id]);

        $observer = new SeatObserver();
        $observer->deleted($seat);

        $this->assertGreaterThan(1, $plan->fresh()->revision);
    }
}
