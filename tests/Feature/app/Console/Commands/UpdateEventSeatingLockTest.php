<?php

namespace Tests\Feature\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\UpdateEventSeatingLock;
use App\Models\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class UpdateEventSeatingLockTest extends TestCase
{
    use RefreshDatabase;

    // CHECK Proposal to fix this is to rework the handle method, feasible?
    public function testCanInstantiateCommand()
    {
        $command = new UpdateEventSeatingLock();
        $this->assertInstanceOf(UpdateEventSeatingLock::class, $command);
    }

    public function testUnlocksSeatingWhenOpensAtIsPast()
    {
        $event = Event::factory()->create([
            'seating_opens_at' => Carbon::now()->subMinute(),
            'seating_locked' => true,
        ]);
        Log::shouldReceive('info')->once();
        $this->artisan('control:update-event-seating-locks');
        $event->refresh();
        $this->assertFalse($event->seating_locked);
        $this->assertNull($event->seating_opens_at);
    }

    public function testLocksSeatingWhenClosesAtIsPast()
    {
        $event = Event::factory()->create([
            'seating_closes_at' => Carbon::now()->subMinute(),
            'seating_locked' => false,
        ]);
        Log::shouldReceive('info')->once();
        $command = new UpdateEventSeatingLock();
        $this->artisan('control:update-event-seating-locks');
        $event->refresh();
        $this->assertTrue($event->seating_locked);
        $this->assertNull($event->seating_closes_at);
    }
}
