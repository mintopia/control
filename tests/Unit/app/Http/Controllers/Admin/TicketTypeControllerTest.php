<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\TicketTypeController;
use App\Http\Requests\Admin\DeleteRequest;
use App\Http\Requests\Admin\TicketTypeUpdateRequest;
use App\Models\Event;
use App\Models\TicketType;
use App\Services\DiscordApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use ReflectionClass;
use Tests\TestCase;

// ...existing code...

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

    public function testCreateAndEditWithDiscordRoles()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();

        // create a PHPUnit mock of the DiscordApi so no network calls are made
        $discord = $this->createMock(DiscordApi::class);
        $discord->method('getRoles')->willReturn(['r1' => 'Role One', 'r2' => 'Role Two']);

        $c = new TicketTypeController();
        $createResp = $c->create($discord, $event);
        $this->assertTrue(is_object($createResp));
        $this->assertArrayHasKey('roles', $createResp->getData());
        $this->assertArrayHasKey('r1', $createResp->getData()['roles']);

        $editResp = $c->edit($discord, $event, $type);
        $this->assertTrue(is_object($editResp));
        $this->assertArrayHasKey('roles', $editResp->getData());
        $this->assertArrayHasKey('r2', $editResp->getData()['roles']);
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
        $req = TicketTypeUpdateRequest::create('/', 'POST', ['name' => 'TT1', 'has_seat' => 1]);
        try {
            $controller->store($req, $event);
        } catch (UrlGenerationException $ex) {
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
        $ref = new ReflectionClass($controller);
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
        $req = TicketTypeUpdateRequest::create('/', 'POST', ['name' => 'After', 'has_seat' => 0]);
        try {
            $controller->update($req, $event, $type);
        } catch (UrlGenerationException $ex) {
            // ignore
        }
        $this->assertEquals('After', $type->fresh()->name);
    }

    public function testDestroyDeletesType()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $controller = new TicketTypeController();
        $req = DeleteRequest::create('/', 'POST', []);
        try {
            $controller->destroy($req, $event, $type);
        } catch (UrlGenerationException $ex) {
            // ignore
        }
        $this->assertNull(TicketType::find($type->id));
    }
}
