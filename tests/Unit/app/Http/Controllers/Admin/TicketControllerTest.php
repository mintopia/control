<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\TicketController;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\TicketProvider;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexAndViews()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        $ticket = Ticket::factory()->for($event)->for($type, 'type')->for($provider, 'provider')->create();

        $c = new TicketController();
        $this->assertTrue(is_object($c->index(request())));
        $this->assertTrue(is_object($c->show($ticket)));
        $this->assertTrue(is_object($c->edit($ticket)));
        $this->assertTrue(is_object($c->delete($ticket)));
    }
}
