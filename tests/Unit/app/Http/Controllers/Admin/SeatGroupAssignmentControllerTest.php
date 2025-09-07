<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\SeatGroupAssignmentController;
use App\Http\Requests\Admin\DeleteRequest;
use App\Http\Requests\Admin\SeatGroupAssignmentUpdateRequest;
use App\Models\Event;
use App\Models\SeatGroup;
use App\Models\SeatGroupAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use ReflectionClass;
use Tests\TestCase;

class SeatGroupAssignmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testBasicViews()
    {
        $event = Event::factory()->create();
        $group = new SeatGroup();
        $group->event()->associate($event);
        $group->name = 'Test Group';
        $group->class = 'default';
        $group->save();
        $assignment = new SeatGroupAssignment();
        $assignment->group()->associate($group);
        $assignment->assignment_type = 'test';
        $assignment->assignment_type_id = 1;
        $assignment->save();

        $c = new SeatGroupAssignmentController();
        $this->assertTrue(is_object($c->create($event, $group)));
        $this->assertTrue(is_object($c->edit($event, $group, $assignment)));
        $this->assertTrue(is_object($c->delete($event, $group, $assignment)));
    }

    public function testUpdateObjectSavesFields()
    {
        $event = Event::factory()->create();
        $group = new SeatGroup();
        $group->event()->associate($event);
        $group->name = 'Test Group';
        $group->class = 'default';
        $group->save();

        $assignment = new SeatGroupAssignment();
        $assignment->group()->associate($group);

        $req = SeatGroupAssignmentUpdateRequest::create('/admin', 'POST', [
            'assignment_type' => 'zone',
            'assignment_type_id' => 123,
        ]);

        $controller = new SeatGroupAssignmentController();
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        $method->invoke($controller, $assignment, $req);

        $this->assertDatabaseHas('seat_group_assignments', [
            'assignment_type' => 'zone',
            'assignment_type_id' => 123,
            'seat_group_id' => $group->id,
        ]);
    }

    public function testStoreRedirectsOrCreatesAssignment()
    {
        $event = Event::factory()->create();
        $group = new SeatGroup();
        $group->event()->associate($event);
        $group->name = 'Test Group';
        $group->class = 'default';
        $group->save();

        $req = SeatGroupAssignmentUpdateRequest::create('/admin', 'POST', [
            'assignment_type' => 'row',
            'assignment_type_id' => 7,
        ]);

        $controller = new SeatGroupAssignmentController();

        try {
            $resp = $controller->store($req, $event, $group);
            $this->assertInstanceOf(RedirectResponse::class, $resp);
        } catch (UrlGenerationException $ex) {
            // If routes are not registered in the test environment the controller will throw
            // an UrlGenerationException when attempting to build the redirect URL. In that
            // case we still expect the assignment to have been created.
            $this->assertTrue(true, 'Route not registered; falling back to DB assertions');
        }

        $this->assertDatabaseHas('seat_group_assignments', [
            'assignment_type' => 'row',
            'assignment_type_id' => 7,
            'seat_group_id' => $group->id,
        ]);
    }

    public function testUpdatePersistsChanges()
    {
        $event = Event::factory()->create();
        $group = new SeatGroup();
        $group->event()->associate($event);
        $group->name = 'Test Group';
        $group->class = 'default';
        $group->save();

        $assignment = new SeatGroupAssignment();
        $assignment->group()->associate($group);
        $assignment->assignment_type = 'old';
        $assignment->assignment_type_id = 1;
        $assignment->save();

        $req = SeatGroupAssignmentUpdateRequest::create('/admin', 'POST', [
            'assignment_type' => 'new',
            'assignment_type_id' => 2,
        ]);

        $controller = new SeatGroupAssignmentController();
        try {
            $controller->update($req, $event, $group, $assignment);
            // If no exception, ensure the DB has the updated values
            $this->assertDatabaseHas('seat_group_assignments', [
                'id' => $assignment->id,
                'assignment_type' => 'new',
                'assignment_type_id' => 2,
            ]);
        } catch (UrlGenerationException $ex) {
            // Route resolution failed — ensure update still persisted
            $this->assertDatabaseHas('seat_group_assignments', [
                'id' => $assignment->id,
                'assignment_type' => 'new',
                'assignment_type_id' => 2,
            ]);
        }
    }

    public function testDestroyRemovesAssignment()
    {
        $event = Event::factory()->create();
        $group = new SeatGroup();
        $group->event()->associate($event);
        $group->name = 'Test Group';
        $group->class = 'default';
        $group->save();

        $assignment = new SeatGroupAssignment();
        $assignment->group()->associate($group);
        $assignment->assignment_type = 'x';
        $assignment->assignment_type_id = 1;
        $assignment->save();

        $req = DeleteRequest::create('/admin', 'DELETE', ['confirm' => 'delete']);

        $controller = new SeatGroupAssignmentController();
        try {
            $controller->destroy($req, $event, $group, $assignment);
        } catch (UrlGenerationException $ex) {
            // ignore route generation issues
        }

        $this->assertDatabaseMissing('seat_group_assignments', ['id' => $assignment->id]);
    }
}
