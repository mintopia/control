<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\SeatObserver;
use App\Models\Seat;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $ticket = \App\Models\Ticket::factory()->create();
        $seat1 = \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);
        $seat2 = \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id]);

        // simulate saving seat1 triggers disassociation of seat2 (if it had same ticket)
        $seat2->ticket()->associate($ticket);
        $seat2->save();

        $observer = new SeatObserver();
        $observer->saved($seat1);

        $this->assertNull($seat2->fresh()->ticket_id);
    }

    public function testSavedDisassociatesOtherSeatInDifferentPlanAndUpdatesOtherPlanRevision()
    {
        $event = \App\Models\Event::factory()->create();
        $planA = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);
        $planB = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $ticket = \App\Models\Ticket::factory()->create(['event_id' => $event->id]);

        // Seat in plan A (the one being saved)
        $seatA = \App\Models\Seat::factory()->create(['seating_plan_id' => $planA->id, 'ticket_id' => $ticket->id]);
        // Another seat in a different plan with same ticket
        $seatB = \App\Models\Seat::factory()->create(['seating_plan_id' => $planB->id, 'ticket_id' => $ticket->id]);

        $observer = new SeatObserver();
        $observer->saved($seatA);

        // other seat should have been disassociated
        $this->assertNull($seatB->fresh()->ticket_id);
        // and the other plan's revision should have been incremented
        $this->assertGreaterThan(1, $planB->fresh()->revision);
    }

    public function testDeletedUpdatesPlanRevision()
    {
        $plan = \App\Models\SeatingPlan::factory()->create(['revision' => 1]);
        $seat = \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id]);

        $observer = new SeatObserver();
        $observer->deleted($seat);

        $this->assertGreaterThan(1, $plan->fresh()->revision);
    }
}
