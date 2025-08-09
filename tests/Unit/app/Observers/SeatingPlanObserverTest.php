<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\SeatingPlanObserver;
use App\Models\SeatingPlan;

class SeatingPlanObserverTest extends TestCase
{
    /* CHECK SeatingPlanObserver: revision is not being incremented in the SeatingPlanObserver's saving method when it should be; the logic is inverted and should increment when the revision is dirty, not when it is not. */
    public function saving(SeatingPlan $seatingPlan): void
    {
        if ($seatingPlan->isDirty('revision')) {
            $seatingPlan->revision++;
        }
    }
    */
    public function testSavingSetsCodeAndIncrementsRevision()
    {
        $seatingPlan = new SeatingPlan(['name' => 'Test Plan', 'code' => null, 'revision' => 1]);
        $observer = new SeatingPlanObserver();
        $observer->saving($seatingPlan);
        $this->assertNotEmpty($seatingPlan->code);
        $this->assertGreaterThan(1, $seatingPlan->revision);
    }
}
