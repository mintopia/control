<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketTest extends TestCase
{
    use RefreshDatabase;
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
        $ticket = \App\Models\Ticket::factory()->create([
            'transfer_code' => null,
        ]);

        $ticket->generateTransferCode();
        $ticket->refresh();

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $ticket->transfer_code);
        $this->assertNotNull($ticket->transfer_code);
    }

    public function testCanTransferReturnsFalseIfEventEnded()
    {
        $event = Event::factory()->create([
            'ends_at' => now()->subDay(),
        ]);
        $ticket = \App\Models\Ticket::factory()->create([
            'event_id' => $event->id,
        ]);
        $this->assertFalse($ticket->canTransfer());
    }

    public function testCanPickSeatReturnsFalseIfTypeHasNoSeat()
    {
        $ticket = new Ticket();
        $mockType = (object)['has_seat' => false];
        $ticket->setRelation('type', $mockType);
        $this->assertFalse($ticket->canPickSeat());
    }

    public function testCanPickSeatReturnsFalseIfEventEnded()
    {
        $ticket = new Ticket();
        $mockType = (object)['has_seat' => true];
        $mockEvent = (object)['ends_at' => now()->subDay(), 'seating_locked' => false];
        $ticket->setRelation('type', $mockType);
        $ticket->setRelation('event', $mockEvent);
        $this->assertFalse($ticket->canPickSeat());
    }

    public function testCanPickSeatReturnsFalseIfSeatingLocked()
    {
        $ticket = new Ticket();
        $mockType = (object)['has_seat' => true];
        $mockEvent = (object)['ends_at' => now()->addDay(), 'seating_locked' => true];
        $ticket->setRelation('type', $mockType);
        $ticket->setRelation('event', $mockEvent);
        $this->assertFalse($ticket->canPickSeat());
    }

    public function testCanPickSeatReturnsTrueIfAllConditionsMet()
    {
        $ticket = new Ticket();
        $mockType = (object)['has_seat' => true];
        $mockEvent = (object)['ends_at' => now()->addDay(), 'seating_locked' => false];
        $ticket->setRelation('type', $mockType);
        $ticket->setRelation('event', $mockEvent);
        $this->assertTrue($ticket->canPickSeat());
    }

    public function testCanBeManagedByReturnsTrueIfUserOwnsTicket()
    {
        $user = User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create([
            'user_id' => $user->id,
        ]);
        $this->assertTrue($ticket->canBeManagedBy($user));
    }

    // TODO Function is not yet built TBC
    public function testCanTransferReturnsTrueIfEventNotEnded()
    {
        $event = Event::factory()->create([
            'ends_at' => now()->addDay(),
        ]);
        $ticket = \App\Models\Ticket::factory()->create([
            'event_id' => $event->id,
        ]);
        $this->assertTrue($ticket->canTransfer());
    }
}
