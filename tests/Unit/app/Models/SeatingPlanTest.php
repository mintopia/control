<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\SeatingPlan;

class SeatingPlanTest extends TestCase
{
    public function testCanInstantiateSeatingPlan()
    {
        $plan = new SeatingPlan();
        $this->assertInstanceOf(SeatingPlan::class, $plan);
    }
}
