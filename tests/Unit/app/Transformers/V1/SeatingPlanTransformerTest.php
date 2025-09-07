<?php

namespace Tests\Unit\app\Transformers\V1;

use App\Models\Event;
use App\Models\SeatingPlan;
use App\Models\User;
use App\Transformers\V1\SeatingPlanTransformer;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SeatingPlanTransformerTest extends TestCase
{
    public function testTransformReturnsExpectedData()
    {
        $plan = new SeatingPlan();
        $plan->id = 1;
        $plan->code = 'SP1';
        $plan->name = 'Main Plan';
        $plan->revision = 2;
        $plan->order = 3;
        $plan->created_at = Carbon::parse('2022-01-01T10:00:00Z');
        $plan->updated_at = Carbon::parse('2022-01-01T12:00:00Z');
        $user = $this->createMock(User::class);
        $user->method('hasRole')->willReturn(false);
        $transformer = new SeatingPlanTransformer($user);
        $result = $transformer->transform($plan);
        $this->assertEquals([
            'id' => 1,
            'code' => 'SP1',
            'name' => 'Main Plan',
            'revision' => 2,
            'order' => 3,
        ], $result);
    }

    public function testIncludeEventReturnsItemResource()
    {
        $plan = new SeatingPlan();
        $plan->setRelation('event', new Event());
        $transformer = new SeatingPlanTransformer();
        $result = $transformer->includeEvent($plan);
        $this->assertNotNull($result);
    }
}
