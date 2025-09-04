<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\SeatController;
use App\Models\Event;
use App\Models\SeatingPlan;
use App\Models\Seat;
use Illuminate\Http\Request;

class SeatControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new SeatController();
        $this->assertInstanceOf(SeatController::class, $controller);
    }

    public function testCreateReturnsView()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        $controller = new SeatController();
        $resp = $controller->create($event, $plan);
        $this->assertTrue(is_object($resp));
    }

    public function testShowEditDeleteReturnViews()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->for($plan, 'plan')->create();

        $controller = new SeatController();
        $this->assertTrue(is_object($controller->show($event, $plan, $seat)));
        $this->assertTrue(is_object($controller->edit($event, $plan, $seat)));
        $this->assertTrue(is_object($controller->delete($event, $plan, $seat)));
    }
}
