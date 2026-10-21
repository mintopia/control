<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\SeatController;
use App\Http\Requests\Admin\SeatUpdateRequest;
use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatingPlan;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use ReflectionClass;
use Tests\TestCase;

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

    public function testStoreCreatesSeatAndRedirects()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        $request = SeatUpdateRequest::create('/admin', 'POST', [
            'x' => 10,
            'y' => 20,
            'row' => 'A',
            'number' => 1,
            'label' => 'A1',
            'description' => 'Front',
            'class' => 'vip',
            'seat_group' => null,
            'disabled' => false,
        ]);

        $controller = new SeatController();
        $resp = $controller->store($request, $event, $plan);

        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertDatabaseHas('seats', ['label' => 'A1', 'seating_plan_id' => $plan->id]);
    }

    public function testUpdateObjectSetsProperties()
    {
        $seat = Seat::factory()->create();
        $controller = new SeatController();

        $request = Request::create('/admin', 'POST', [
            'x' => 5,
            'y' => 6,
            'row' => 'B',
            'number' => 2,
            'label' => 'B2',
            'description' => 'Back',
            'class' => 'standard',
            'seat_group' => null,
            'disabled' => true,
        ]);

        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        $method->invoke($controller, $seat, $request);

        $this->assertEquals(5, $seat->x);
        $this->assertEquals('B2', $seat->label);
        $this->assertTrue((bool)$seat->disabled);
    }

    public function testUpdatePersistsChanges()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->for($plan, 'plan')->create(['label' => 'Old']);

        $request = SeatUpdateRequest::create('/admin', 'POST', [
            'x' => 1,
            'y' => 1,
            'row' => 'C',
            'number' => 3,
            'label' => 'New',
            'description' => '',
            'class' => '',
            'seat_group' => null,
            'disabled' => false,
        ]);

        $controller = new SeatController();
        $resp = $controller->update($request, $event, $plan, $seat);

        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertDatabaseHas('seats', ['id' => $seat->id, 'label' => 'New']);
    }

    public function testDestroyDeletesSeat()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->for($plan, 'plan')->create();

        $controller = new SeatController();
        $resp = $controller->destroy($event, $plan, $seat);

        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertDatabaseMissing('seats', ['id' => $seat->id]);
    }

    public function testUnseatDisassociatesTicket()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->for($plan, 'plan')->create();

        // create a ticket and associate
        $ticket = Ticket::factory()->create();
        $seat->ticket()->associate($ticket);
        $seat->save();

        $controller = new SeatController();
        $resp = $controller->unseat($event, $plan, $seat);

        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertNull($seat->fresh()->ticket_id);
    }
}
