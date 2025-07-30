<?php

namespace Tests\Unit\app\Jobs;

use Tests\TestCase;
use App\Jobs\UpdateSeatingPlanJob;
use App\Models\SeatingPlan;

class UpdateSeatingPlanJobTest extends TestCase
{
    public function testJobCanBeInstantiated()
    {
        $plan = $this->getMockBuilder(SeatingPlan::class)->disableOriginalConstructor()->getMock();
        $job = new UpdateSeatingPlanJob($plan, 1);
        $this->assertInstanceOf(UpdateSeatingPlanJob::class, $job);
    }
}
