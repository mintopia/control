<?php

namespace Tests\Unit\app\Observers;

use App\Models\Event;
use App\Models\SeatingPlan;
use App\Observers\EventObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        // mark dirty
        $event->seating_locked = true;
        $observer = new EventObserver();
        $observer->saved($event);

        $this->assertGreaterThan(1, $plan->fresh()->revision);
    }
}
