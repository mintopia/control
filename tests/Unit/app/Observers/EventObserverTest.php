<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\EventObserver;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventObserverTest extends TestCase
{
    use RefreshDatabase;
    public function testSavingSetsCodeIfMissing()
    {
        $event = new Event(['name' => 'Test Event', 'code' => null]);
        $observer = new EventObserver();
        $observer->saving($event);
        $this->assertNotEmpty($event->code);
    }

    public function testSavedIncrementsRevisionWhenSeatingLockedDirty()
    {
        $event = Event::factory()->create(['seating_locked' => false]);
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        // mark dirty
        $event->seating_locked = true;
        $observer = new EventObserver();
        $observer->saved($event);

        $this->assertGreaterThan(1, $plan->fresh()->revision);
    }


    // NOTE The following tests assert empty observer handlers are currently no-ops.
    public function testCreatedIsNoop()
    {
        $event = Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 21]);

        // Snapshot current revision after any creation observers
        $before = $plan->fresh()->revision;

        $observer = new EventObserver();
        $observer->created($event);

        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testUpdatedIsNoop()
    {
        $event = Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 23]);

        // Snapshot current revision after any creation observers
        $before = $plan->fresh()->revision;

        $observer = new EventObserver();
        $observer->updated($event);

        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testDeletedIsNoop()
    {
        $event = Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 25]);

        // Snapshot current revision after any creation observers
        $before = $plan->fresh()->revision;

        $observer = new EventObserver();
        $observer->deleted($event);

        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testRestoredIsNoop()
    {
        $event = Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 27]);

        // Snapshot current revision after any creation observers
        $before = $plan->fresh()->revision;

        $observer = new EventObserver();
        $observer->restored($event);

        $this->assertEquals($before, $plan->fresh()->revision);
    }

    public function testForceDeletedIsNoop()
    {
        $event = Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 29]);

        // Snapshot current revision after any creation observers
        $before = $plan->fresh()->revision;

        $observer = new EventObserver();
        $observer->forceDeleted($event);

        $this->assertEquals($before, $plan->fresh()->revision);
    }
}
