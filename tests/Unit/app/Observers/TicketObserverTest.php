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
        $planMock = $this->getMockBuilder('stdClass')->onlyMethods(['updateRevision'])->getMock();
        $planMock->expects($this->once())->method('updateRevision');

        // Mock the seat with a plan property
        $seatMock = $this->getMockBuilder('stdClass')->getMock();
        $seatMock->plan = $planMock;

        // Mock the Ticket and override isDirty and seat property via constructor or mocking
        $ticket = $this->getMockBuilder(Ticket::class)
            ->onlyMethods(['isDirty'])
            ->disableOriginalConstructor()
            ->getMock();

        $ticket->method('isDirty')->willReturn(true);

        // Use Reflection to set readonly property seat
        $reflection = new \ReflectionClass($ticket);
        $property = $reflection->getProperty('seat');
        $property->setValue($ticket, $seatMock);

        $observer = new TicketObserver();
        $observer->saved($ticket);
    }
}
