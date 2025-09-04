<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\SeatingPlanController;
use App\Models\Event;
use App\Models\SeatingPlan;

class SeatingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateShowEditDeleteRefresh()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        $c = new SeatingPlanController();
        $this->assertTrue(is_object($c->create($event)));
        $this->assertTrue(is_object($c->show($event, $plan)));
        $this->assertTrue(is_object($c->edit($event, $plan)));
        $this->assertTrue(is_object($c->delete($event, $plan)));
        $this->assertTrue(is_object($c->refresh($event, $plan)));
        $this->assertTrue(is_object($c->up($event, $plan)));
        $this->assertTrue(is_object($c->down($event, $plan)));
    }
}
