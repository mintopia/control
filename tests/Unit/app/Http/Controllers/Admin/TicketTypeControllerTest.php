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

    public function testCreateShowEditDelete()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $c = new TicketTypeController();
        $this->assertTrue(is_object($c->create(null, $event)));
        $this->assertTrue(is_object($c->show($event, $type)));
        $this->assertTrue(is_object($c->edit(null, $event, $type)));
        $this->assertTrue(is_object($c->delete($event, $type)));
    }
}
