<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\SeatGroupController;
use App\Models\Event;
use App\Models\SeatGroup;

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
        $group = new \App\Models\SeatGroup();
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
}
