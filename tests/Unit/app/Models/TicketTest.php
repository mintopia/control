<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\Ticket;

class TicketTest extends TestCase
{
    public function testCanInstantiateTicket()
    {
        $ticket = new Ticket();
        $this->assertInstanceOf(Ticket::class, $ticket);
    }
}
