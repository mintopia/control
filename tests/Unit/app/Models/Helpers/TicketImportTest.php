<?php

namespace Tests\Unit\app\Models\Helpers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Helpers\TicketImport;
use App\Models\User;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\Seat;

class TicketImportTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateTicketImport()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()]);
        $type = TicketType::factory()->create();
        $seat = Seat::factory()->create(['seating_plan_id' => \App\Models\SeatingPlan::factory()->create()->id]);

        $import = new TicketImport($user, $event, $type, $seat);
        $this->assertInstanceOf(TicketImport::class, $import);
        $this->assertSame($user->id, $import->user->id);
    }

    public function testCanInstantiateWithAllArguments()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()]);
        $type = TicketType::factory()->create();
        $seat = Seat::factory()->create(['seating_plan_id' => \App\Models\SeatingPlan::factory()->create()->id]);
        $import = new TicketImport($user, $event, $type, $seat);
        $this->assertSame($user->id, $import->user->id);
        $this->assertSame($event->id, $import->event->id);
        $this->assertSame($type->id, $import->type->id);
        $this->assertSame($seat->id, $import->seat->id);
    }

    public function testCanInstantiateWithNullSeat()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()]);
        $type = TicketType::factory()->create();
        $import = new TicketImport($user, $event, $type, null);
        $this->assertSame($user->id, $import->user->id);
        $this->assertSame($event->id, $import->event->id);
        $this->assertSame($type->id, $import->type->id);
        $this->assertNull($import->seat);
    }
}
