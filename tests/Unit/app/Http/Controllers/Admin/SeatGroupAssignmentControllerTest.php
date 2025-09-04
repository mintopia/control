<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\SeatGroupAssignmentController;
use App\Models\Event;
use App\Models\SeatGroup;
use App\Models\SeatGroupAssignment;

class SeatGroupAssignmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testBasicViews()
    {
        $event = Event::factory()->create();
        $group = new \App\Models\SeatGroup();
        $group->event()->associate($event);
        $group->name = 'Test Group';
        $group->class = 'default';
        $group->save();
        $assignment = new \App\Models\SeatGroupAssignment();
        $assignment->group()->associate($group);
        $assignment->assignment_type = 'test';
        $assignment->assignment_type_id = 1;
        $assignment->save();

        $c = new SeatGroupAssignmentController();
        $this->assertTrue(is_object($c->create($event, $group)));
        $this->assertTrue(is_object($c->edit($event, $group, $assignment)));
        $this->assertTrue(is_object($c->delete($event, $group, $assignment)));
    }
}
