<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\TicketTypeController;
use App\Models\Event;
use App\Models\TicketType;

class TicketTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateReturnsView()
    {
        $event = Event::factory()->create();
        $c = new TicketTypeController();
        $this->assertTrue(is_object($c->create(null, $event)));
    }

    public function testShowReturnsView()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $c = new TicketTypeController();
        $this->assertTrue(is_object($c->show($event, $type)));
    }

    public function testEditReturnsView()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $c = new TicketTypeController();
        $this->assertTrue(is_object($c->edit(null, $event, $type)));
    }

    public function testDeleteReturnsView()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $c = new TicketTypeController();
        $this->assertTrue(is_object($c->delete($event, $type)));
    }

    public function testStoreCreatesTicketType()
    {
        $event = Event::factory()->create();
        $controller = new TicketTypeController();
        $req = \App\Http\Requests\Admin\TicketTypeUpdateRequest::create('/', 'POST', ['name' => 'TT1', 'has_seat' => 1]);
        try {
            $controller->store($req, $event);
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $ex) {
            // ignore missing route
        }
        $this->assertDatabaseHas('ticket_types', ['name' => 'TT1']);
    }

    public function testUpdateObjectSetsFields()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create(['name' => 'Old', 'has_seat' => 0]);
        $controller = new TicketTypeController();
        $req = request()->create('/', 'POST', ['name' => 'NewName', 'has_seat' => 1, 'discord_role_id' => 'r1']);
        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        $method->invoke($controller, $type, $req);
        $this->assertEquals('NewName', $type->fresh()->name);
        $this->assertEquals(1, $type->fresh()->has_seat);
        $this->assertEquals('r1', $type->fresh()->discord_role_id);
    }

    public function testUpdatePersistsChanges()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create(['name' => 'Before']);
        $controller = new TicketTypeController();
        $req = \App\Http\Requests\Admin\TicketTypeUpdateRequest::create('/', 'POST', ['name' => 'After', 'has_seat' => 0]);
        try {
            $controller->update($req, $event, $type);
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $ex) {
            // ignore
        }
        $this->assertEquals('After', $type->fresh()->name);
    }

    public function testDestroyDeletesType()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $controller = new TicketTypeController();
        $req = \App\Http\Requests\Admin\DeleteRequest::create('/', 'POST', []);
        try {
            $controller->destroy($req, $event, $type);
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $ex) {
            // ignore
        }
        $this->assertNull(\App\Models\TicketType::find($type->id));
    }
}
