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

    public function testDeletedUpdatesPlanRevision()
    {
        $plan = \App\Models\SeatingPlan::factory()->create(['revision' => 1]);
        $seat = \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id]);

        $observer = new SeatObserver();
        $observer->deleted($seat);

        $this->assertGreaterThan(1, $plan->fresh()->revision);
    }


    // NOTE The following tests are recorded to fail once the observers are built out properly.
    public function testCreatedIsPendingImplementation()
    {
        $plan = \App\Models\SeatingPlan::factory()->create(['revision' => 11]);
        $seat = \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id]);

        // Snapshot current revision after any save observers have run
        $before = $plan->fresh()->revision;

        $observer = new SeatObserver();
        $observer->created($seat);

        // created() is currently a no-op; revision should remain unchanged
        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testUpdatedIsPendingImplementation()
    {
        $plan = \App\Models\SeatingPlan::factory()->create(['revision' => 13]);
        $seat = \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id]);

        // Snapshot current revision after any save observers have run
        $before = $plan->fresh()->revision;

        $observer = new SeatObserver();
        $observer->updated($seat);

        // updated() is currently a no-op; revision should remain unchanged
        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testRestoredIsPendingImplementation()
    {
        $plan = \App\Models\SeatingPlan::factory()->create(['revision' => 5]);
        $seat = \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id]);

        // Snapshot current revision after any save observers have run
        $before = $plan->fresh()->revision;

        $observer = new SeatObserver();
        $observer->restored($seat);

        // restored() is currently a no-op; revision should remain unchanged
        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testForceDeletedIsPendingImplementation()
    {
        $plan = \App\Models\SeatingPlan::factory()->create(['revision' => 7]);
        $seat = \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id]);

        // Snapshot current revision after any save observers have run
        $before = $plan->fresh()->revision;

        $observer = new SeatObserver();
        $observer->forceDeleted($seat);

        // forceDeleted() is currently a no-op; revision should remain unchanged
        $this->assertEquals($before, $plan->fresh()->revision);
    }
}
