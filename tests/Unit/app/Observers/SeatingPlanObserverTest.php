<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\SeatingPlanObserver;
use App\Models\SeatingPlan;

class SeatingPlanObserverTest extends TestCase
{
    public function testSavingSetsCodeAndIncrementsRevision()
    {
        $seatingPlan = new SeatingPlan(['name' => 'Test Plan', 'code' => null, 'revision' => 1]);
        $observer = new SeatingPlanObserver();
        $observer->saving($seatingPlan);
        $this->assertNotEmpty($seatingPlan->code);
        $this->assertGreaterThan(1, $seatingPlan->revision);
    }
}
