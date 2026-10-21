<?php

namespace Tests\Feature\app\Console\Commands;

use App\Console\Commands\UpdateEventSeatingLock;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UpdateEventSeatingLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Make times deterministic for the tests
        Carbon::setTestNow(Carbon::parse('2025-08-19 12:00:00'));
    }

    protected function tearDown(): void
    {
        // Clear test now so other tests are unaffected
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testCanInstantiateCommand()
    {
        $command = new UpdateEventSeatingLock();
        $this->assertInstanceOf(UpdateEventSeatingLock::class, $command);
    }

    public function testUnlocksSeatingWhenOpensAtIsPast()
    {
        $event = Event::factory()->opened()->create([
            // opened() sets seating_opens_at in the past; override locked to match test precondition
            'seating_locked' => true,
        ]);
        Log::spy();
        $this->artisan('control:update-event-seating-locks');
        $event->refresh();
        $this->assertFalse($event->seating_locked);
        $this->assertNull($event->seating_opens_at);
        Log::shouldHaveReceived('info')->once();
    }

    public function testLocksSeatingWhenClosesAtIsPast()
    {
        $event = Event::factory()->closed()->create([
            // closed() sets seating_closes_at in the past; override locked to match test precondition
            'seating_locked' => false,
        ]);

        Log::spy(); // less brittle
        $this->artisan('control:update-event-seating-locks');
        $event->refresh();

        $this->assertTrue($event->seating_locked);
        $this->assertNull($event->seating_closes_at);

        Log::shouldHaveReceived('info')->once();
    }
}
