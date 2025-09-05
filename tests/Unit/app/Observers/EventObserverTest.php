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

    public function testEmptyHandlersAreSkipped()
    {
        $this->markTestSkipped('EventObserver empty handlers not implemented yet');
    }
}
