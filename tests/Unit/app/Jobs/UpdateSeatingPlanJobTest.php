<?php

namespace Tests\Unit\app\Jobs;

use App\Jobs\UpdateSeatingPlanJob;
use App\Models\SeatingPlan;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

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
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->once())->method('debug')->with($this->stringContains($message));
        Log::swap($logger);

        $job = new UpdateSeatingPlanJob($plan, 1);
        $job->handle();
    }

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
