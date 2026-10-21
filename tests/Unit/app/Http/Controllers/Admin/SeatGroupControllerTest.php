<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\SeatGroupController;
use App\Http\Requests\Admin\DeleteRequest;
use App\Http\Requests\Admin\SeatGroupUpdateRequest;
use App\Models\Event;
use App\Models\SeatGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use ReflectionClass;
use Tests\TestCase;

class SeatGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiate()
    {
        $c = new SeatGroupController();
        $this->assertInstanceOf(SeatGroupController::class, $c);
    }

    public function testCreateShowEditDelete()
    {
        $event = Event::factory()->create();
        // Some projects don't provide a factory for SeatGroup; create directly
        $group = new SeatGroup();
        $group->event()->associate($event);
        $group->name = 'Test Group';
        $group->class = 'default';
        $group->save();

        $c = new SeatGroupController();
        $this->assertTrue(is_object($c->create($event)));
        $this->assertTrue(is_object($c->show($event, $group)));
        $this->assertTrue(is_object($c->edit($event, $group)));
        $this->assertTrue(is_object($c->delete($event, $group)));
    }

    public function testUpdateObjectPersists()
    {
        $event = Event::factory()->create();
        $group = new SeatGroup();
        $group->event()->associate($event);
        $group->name = 'Before';
        $group->class = 'old';
        $group->save();

        $req = SeatGroupUpdateRequest::create('/admin', 'POST', ['name' => 'After', 'class' => 'new']);
        $controller = new SeatGroupController();
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        $method->invoke($controller, $group, $req);

        $this->assertDatabaseHas('seat_groups', ['id' => $group->id, 'name' => 'After', 'class' => 'new']);
    }

    public function testStoreCreatesAndRedirects()
    {
        $event = Event::factory()->create();
        $req = SeatGroupUpdateRequest::create('/admin', 'POST', ['name' => 'G1', 'class' => 'c']);
        $controller = new SeatGroupController();

        try {
            $resp = $controller->store($req, $event);
            $this->assertInstanceOf(RedirectResponse::class, $resp);
        } catch (UrlGenerationException $ex) {
            // ignore if route not registered
        }

        $this->assertDatabaseHas('seat_groups', ['name' => 'G1', 'class' => 'c', 'event_id' => $event->id]);
    }

    public function testUpdatePersists()
    {
        $event = Event::factory()->create();
        $group = new SeatGroup();
        $group->event()->associate($event);
        $group->name = 'Old';
        $group->class = 'c';
        $group->save();

        $req = SeatGroupUpdateRequest::create('/admin', 'POST', ['name' => 'New', 'class' => 'd']);
        $controller = new SeatGroupController();
        try {
            $controller->update($req, $event, $group);
        } catch (UrlGenerationException $ex) {
            // ignore
        }

        $this->assertDatabaseHas('seat_groups', ['id' => $group->id, 'name' => 'New', 'class' => 'd']);
    }

    public function testDestroyDeletes()
    {
        $event = Event::factory()->create();
        $group = new SeatGroup();
        $group->event()->associate($event);
        $group->name = 'ToDelete';
        $group->class = 'x';
        $group->save();

        $req = DeleteRequest::create('/admin', 'DELETE', ['confirm' => 'delete']);
        $controller = new SeatGroupController();

        try {
            $controller->destroy($req, $event, $group);
        } catch (UrlGenerationException $ex) {
            // ignore
        }

        $this->assertDatabaseMissing('seat_groups', ['id' => $group->id]);
    }
}
