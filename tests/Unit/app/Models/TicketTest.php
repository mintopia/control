<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\Event;
use App\Models\User;
use App\Models\Seat;
use App\Models\SeatingPlan;
use App\Models\TicketProvider;
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
        $type = \App\Models\TicketType::factory()->create([
            'has_seat' => false,
        ]);
        $event = Event::factory()->create([
            'ends_at' => now()->addDay(),
            'seating_locked' => false,
        ]);
        $ticket = \App\Models\Ticket::factory()->create([
            'ticket_type_id' => $type->id,
            'event_id' => $event->id,
        ]);
        $this->assertFalse($ticket->canPickSeat());
    }

    public function testCanPickSeatReturnsFalseIfEventEnded()
    {
        $type = \App\Models\TicketType::factory()->create([
            'has_seat' => true,
        ]);
        $event = Event::factory()->create([
            'ends_at' => now()->subDay(),
            'seating_locked' => false,
        ]);
        $ticket = \App\Models\Ticket::factory()->create([
            'ticket_type_id' => $type->id,
            'event_id' => $event->id,
        ]);
        $this->assertFalse($ticket->canPickSeat());
    }

    public function testCanPickSeatReturnsFalseIfSeatingLocked()
    {
        $type = \App\Models\TicketType::factory()->create([
            'has_seat' => true,
        ]);
        $event = Event::factory()->create([
            'ends_at' => now()->addDay(),
            'seating_locked' => true,
        ]);
        $ticket = \App\Models\Ticket::factory()->create([
            'ticket_type_id' => $type->id,
            'event_id' => $event->id,
        ]);
        $this->assertFalse($ticket->canPickSeat());
    }

    public function testCanPickSeatReturnsTrueIfAllConditionsMet()
    {
        $type = \App\Models\TicketType::factory()->create([
            'has_seat' => true,
        ]);
        $event = Event::factory()->create([
            'ends_at' => now()->addDay(),
            'seating_locked' => false,
        ]);
        $ticket = \App\Models\Ticket::factory()->create([
            'ticket_type_id' => $type->id,
            'event_id' => $event->id,
        ]);
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

    public function testGenerateTransferCodeProducesDifferentCodesWhenCalledTwice()
    {
        $ticket = \App\Models\Ticket::factory()->create([
            'transfer_code' => null,
        ]);

        $ticket->generateTransferCode();
        $first = $ticket->transfer_code;
        $ticket->generateTransferCode();
        $ticket->refresh();
        $second = $ticket->transfer_code;

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $first);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $second);
        $this->assertNotEquals($first, $second);
    }

    public function testCanBeManagedBy()
    {
        $user = User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create([
            'user_id' => $user->id,
        ]);
        $this->assertTrue($ticket->canBeManagedBy($user));
    }

    public function testImportParsesCsvAndCreatesTicketImportObjects()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->create(['event_id' => $event->id, 'has_seat' => 1]);
        $user = User::factory()->create();

        // Create seating plan and seat to exercise seat lookup
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id, 'label' => 'A1']);

        // CSV format: ticket_type_id,user_id,seat_label
        $csv = "ticket_type_id,user_id,seat_label\n";
        $csv .= "{$type->id},{$user->id},A1\n";

        $imports = Ticket::import($csv);

        $this->assertIsArray($imports);
        $this->assertCount(1, $imports);
        $import = $imports[0];

        $this->assertEquals($user->id, $import->user->id);
        $this->assertEquals($event->id, $import->event->id);
        $this->assertEquals($type->id, $import->type->id);
        $this->assertNotNull($import->seat);
        $this->assertEquals('A1', $import->seat->label);
    }

    public function testImportSkipsRowWhenTypeMissing()
    {
        $event = Event::factory()->create();
        $user = User::factory()->create();

        // Create seating plan and seat to exercise seat lookup
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id, 'label' => 'A1']);

        // CSV with non-existing ticket type id
        $csv = "ticket_type_id,user_id,seat_label\n";
        $csv .= "999999,{$user->id},A1\n";

        $imports = Ticket::import($csv);

        $this->assertIsArray($imports);
        $this->assertCount(0, $imports);
    }

    public function testImportSkipsRowWhenUserMissing()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->create(['event_id' => $event->id, 'has_seat' => 1]);

        // Create seating plan and seat to exercise seat lookup
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id, 'label' => 'A1']);

        // CSV with non-existing user id
        $csv = "ticket_type_id,user_id,seat_label\n";
        $csv .= "{$type->id},999999,A1\n";

        $imports = Ticket::import($csv);

        $this->assertIsArray($imports);
        $this->assertCount(0, $imports);
    }

    public function testCreateFromImport()
    {
        // Arrange: ensure there is an 'internal' ticket provider referenced by createFromImport
        TicketProvider::factory()->create(['code' => 'internal']);

        $event = Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addDay(), 'seating_locked' => false]);
        $type = TicketType::factory()->create(['event_id' => $event->id, 'has_seat' => 1]);
        $user = User::factory()->create();

        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id, 'label' => 'B1']);

        $import = new \App\Models\Helpers\TicketImport($user, $event, $type, $seat);

        // Act
        $ticket = Ticket::createFromImport($import);

        // Assert
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals($event->id, $ticket->event_id);
        $this->assertEquals($type->id, $ticket->ticket_type_id);
        $this->assertEquals($user->id, $ticket->user_id);

        $seat->refresh();
        $this->assertEquals($ticket->id, $seat->ticket_id);
    }
}
