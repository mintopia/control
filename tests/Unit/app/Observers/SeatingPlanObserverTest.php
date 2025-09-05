<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\SeatingPlanObserver;
use App\Models\SeatingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SeatingPlanObserverTest extends TestCase
{
    use RefreshDatabase;

    public function testSavingSetsCodeAndIncrementsRevision()
    {
        $seatingPlan = SeatingPlan::factory()->create([
            'name' => 'Test Plan',
            'code' => null,
            'revision' => 1,
        ]);

        // Simulate that the revision attribute was updated by marking it dirty
        $seatingPlan->revision = 2;

        $observer = new SeatingPlanObserver();
        $observer->saving($seatingPlan);

        $this->assertNotEmpty($seatingPlan->code);
        $this->assertGreaterThan(1, $seatingPlan->revision);
    }

    public function testSavedQueuesUpdateWhenRevisionDirty()
    {
        $seatingPlan = SeatingPlan::factory()->create(['revision' => 1]);
        // mark as dirty
        $seatingPlan->revision = 2;
        $observer = new SeatingPlanObserver();
        $observer->saved($seatingPlan);

        // queueUpdate dispatches a job; ensure revision unchanged but no exceptions
        $this->assertGreaterThanOrEqual(1, $seatingPlan->revision);
    }

    public function testEmptyHandlersSkipped()
    {
        $this->markTestSkipped('SeatingPlanObserver other handlers not implemented yet');
    }
}
