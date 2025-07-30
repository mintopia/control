<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\TicketObserver;
use App\Models\Ticket;
use App\Models\User;

class TicketObserverTest extends TestCase
{
    public function testSavedUpdatesPlanRevisionIfDirtyAndHasSeat()
    {
        $ticket = $this->getMockBuilder(Ticket::class)->onlyMethods(['isDirty'])->getMock();
        $ticket->method('isDirty')->willReturn(true);
        $ticket->seat = $this->getMockBuilder('stdClass')->addMethods(['plan'])->getMock();
        $ticket->seat->plan = $this->getMockBuilder('stdClass')->addMethods(['updateRevision'])->getMock();
        $ticket->seat->plan->expects($this->once())->method('updateRevision');
        $observer = new TicketObserver();
        $observer->saved($ticket);
    }
}
