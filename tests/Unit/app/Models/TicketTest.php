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

    public function testUserRelationship()
    {
        $ticket = new Ticket();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $ticket->user());
    }

    public function testProviderRelationship()
    {
        $ticket = new Ticket();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $ticket->provider());
    }

    public function testTypeRelationship()
    {
        $ticket = new Ticket();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $ticket->type());
    }

    public function testEventRelationship()
    {
        $ticket = new Ticket();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $ticket->event());
    }

    public function testSeatRelationship()
    {
        $ticket = new Ticket();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $ticket->seat());
    }

    public function testGenerateTransferCodeSetsCodeAndSaves()
    {
        $ticket = $this->getMockBuilder(Ticket::class)
            ->onlyMethods(['save'])
            ->getMock();
        $ticket->expects($this->once())->method('save');
        $ticket->generateTransferCode();
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $ticket->transfer_code);
    }

    public function testCanTransferReturnsFalseIfEventEnded()
    {
        $ticket = new Ticket();
        $mockEvent = $this->createMock(\App\Models\Event::class);
        $mockEvent->ends_at = now()->subDay();
        $ticket->setRelation('event', $mockEvent);
        $this->assertFalse($ticket->canTransfer());
    }

    // TODO Function is not yet built TBC
    public function testCanTransferReturnsTrueIfEventNotEnded()
    {
        $ticket = new Ticket();
        $mockEvent = $this->createMock(\App\Models\Event::class);
        $mockEvent->ends_at = now()->addDay();
        $ticket->setRelation('event', $mockEvent);
        $this->assertTrue($ticket->canTransfer());
    }

    public function testCanPickSeatReturnsFalseIfTypeHasNoSeat()
    {
        $ticket = new Ticket();
        $mockType = $this->createMock(\App\Models\TicketType::class);
        $mockType->has_seat = false;
        $ticket->setRelation('type', $mockType);
        $this->assertFalse($ticket->canPickSeat());
    }

    public function testCanPickSeatReturnsFalseIfEventEnded()
    {
        $ticket = new Ticket();
        $mockType = $this->createMock(\App\Models\TicketType::class);
        $mockType->has_seat = true;
        $mockEvent = $this->createMock(\App\Models\Event::class);
        $mockEvent->ends_at = now()->subDay();
        $mockEvent->seating_locked = false;
        $ticket->setRelation('type', $mockType);
        $ticket->setRelation('event', $mockEvent);
        $this->assertFalse($ticket->canPickSeat());
    }

    public function testCanPickSeatReturnsFalseIfSeatingLocked()
    {
        $ticket = new Ticket();
        $mockType = $this->createMock(\App\Models\TicketType::class);
        $mockType->has_seat = true;
        $mockEvent = $this->createMock(\App\Models\Event::class);
        $mockEvent->ends_at = now()->addDay();
        $mockEvent->seating_locked = true;
        $ticket->setRelation('type', $mockType);
        $ticket->setRelation('event', $mockEvent);
        $this->assertFalse($ticket->canPickSeat());
    }

    public function testCanPickSeatReturnsTrueIfAllConditionsMet()
    {
        $ticket = new Ticket();
        $mockType = $this->createMock(\App\Models\TicketType::class);
        $mockType->has_seat = true;
        $mockEvent = $this->createMock(\App\Models\Event::class);
        $mockEvent->ends_at = now()->addDay();
        $mockEvent->seating_locked = false;
        $ticket->setRelation('type', $mockType);
        $ticket->setRelation('event', $mockEvent);
        $this->assertTrue($ticket->canPickSeat());
    }

    public function testCanBeManagedByReturnsTrueIfUserOwnsTicket()
    {
        $ticket = new Ticket();
        $ticket->user_id = 1;
        $mockUser = $this->createMock(\App\Models\User::class);
        $mockUser->id = 1;
        $this->assertTrue($ticket->canBeManagedBy($mockUser));
    }
}
