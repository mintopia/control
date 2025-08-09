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
        // Mock the plan with updateRevision expectation
        $planMock = \Mockery::mock();
        $planMock->shouldReceive('updateRevision')->once();

        // Mock the seat with a plan property
        $seatMock = \Mockery::mock();
        $seatMock->plan = $planMock;

        // Create a real Ticket instance and mock only needed methods
        $ticket = new Ticket();
        $ticket->setRelation('seat', $seatMock);

        // Use partial mock to override isDirty
        $ticketPartial = \Mockery::mock($ticket)->makePartial();
        $ticketPartial->shouldReceive('isDirty')->andReturn(true);

        $observer = new TicketObserver();
        $observer->saved($ticketPartial);
    }
}
