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

    public function testJobStoresPlanAndRevision()
    {
        $plan = $this->getMockBuilder(SeatingPlan::class)->disableOriginalConstructor()->getMock();
        $job = new UpdateSeatingPlanJob($plan, 42);
        $this->assertSame($plan, $job->plan);
        $this->assertEquals(42, $job->revision);
    }

    public function testHandleDoesNotUpdateIfRevisionDoesNotMatch()
    {
        $plan = $this->getMockBuilder(SeatingPlan::class)->disableOriginalConstructor()->getMock();
        $plan->revision = 2;
        $plan->method('__toString')->willReturn('PlanA');

        // Standard mock to validate DEBUG message
        $message = 'not updating as';
        $logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)->getMock();
        $logger->expects($this->once())->method('debug')->with($this->stringContains($message));
        \Illuminate\Support\Facades\Log::swap($logger);

        $job = new UpdateSeatingPlanJob($plan, 1);
        $job->handle();
    }

    // FIXME this test does not work as things are missing that should be there
    public function testHandleUpdatesIfRevisionMatches()
    {
        $plan = $this->getMockBuilder(SeatingPlan::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'save'])
            ->getMock();

        $plan->revision = 5;

        // Expect getData to be called once
        $plan->expects($this->once())->method('getData')->willReturn(collect(['foo' => 'bar']));
        // Mock save if handle() calls it
        $plan->expects($this->any())->method('save')->willReturn(true);

        $job = new UpdateSeatingPlanJob($plan, 5);
        $job->handle();
    }

}
