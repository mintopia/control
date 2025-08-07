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

    public function testEventRelationship()
    {
        $plan = new SeatingPlan();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $plan->event());
    }

    public function testSeatsRelationship()
    {
        $plan = new SeatingPlan();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $plan->seats());
    }

    public function testBuildSortQueryReturnsBuilder()
    {
        $plan = new SeatingPlan();
        $plan->event_id = 1;
        $query = $plan->buildSortQuery();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }

    public function testUpdateRevisionIncrementsRevision()
    {
        $plan = $this->getMockBuilder(SeatingPlan::class)
            ->onlyMethods(['save'])
            ->getMock();
        $plan->revision = 1;
        $plan->expects($this->once())->method('save');
        $plan->updateRevision();
        $this->assertEquals(2, $plan->revision);
    }

    public function testToStringNameReturnsCode()
    {
        $plan = new SeatingPlan();
        $plan->code = 'test_code';
        $reflection = new \ReflectionClass($plan);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $this->assertEquals('test_code', $method->invoke($plan));
    }
}
