<?php

namespace Tests\Feature\app\Models;

use App\Jobs\UpdateSeatingPlanJob;
use Database\Factories\SeatFactory;
use Database\Factories\SeatingPlanFactory;
use Database\Factories\TicketFactory;
use Database\Factories\TicketTypeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SeatingPlanExtraTest extends TestCase
{
    use RefreshDatabase;

    public function testGetDataCachesResult()
    {
        $plan = SeatingPlanFactory::new()->create();

        // Create a ticket type and ticket with user
        $type = TicketTypeFactory::new()->create(['event_id' => $plan->event_id, 'has_seat' => true]);
        $ticket = TicketFactory::new()->create(['event_id' => $plan->event_id, 'ticket_type_id' => $type->id]);

        $seat = SeatFactory::new()->create(['seating_plan_id' => $plan->id]);
        $seat->ticket()->associate($ticket);
        $seat->save();

        Cache::flush();

        $key = "seatingplans:{$plan->id}:{$plan->revision}";
        $this->assertFalse(Cache::has($key));

        $data = $plan->getData();
        $this->assertInstanceOf(Collection::class, $data);
        $this->assertTrue(Cache::has($key));

        // Ensure subsequent call hits cache (returns Collection)
        $data2 = $plan->getData();
        $this->assertInstanceOf(Collection::class, $data2);
    }

    public function testDelayedRevisionUpdateDispatchesJob()
    {
        Bus::fake();

        $plan = SeatingPlanFactory::new()->create(['revision' => 1]);
        $plan->delayedRevisionUpdate();

        Bus::assertDispatched(UpdateSeatingPlanJob::class, function ($job) use ($plan) {
            return $job->plan->id === $plan->id;
        });
    }

    public function testQueueUpdateDispatchesJob()
    {
        Bus::fake();
        $plan = SeatingPlanFactory::new()->create(['revision' => 5]);
        $plan->queueUpdate();

        Bus::assertDispatched(UpdateSeatingPlanJob::class, function ($job) use ($plan) {
            return $job->plan->id === $plan->id && $job->revision === $plan->revision;
        });
    }

    public function testImportWithWipeRemovesExistingSeats()
    {
        $plan = SeatingPlanFactory::new()->create(['revision' => 1]);
        // create an existing seat
        $existing = SeatFactory::new()->create(['seating_plan_id' => $plan->id]);

        $csv = "ID,x,y,row,number,label,description,class,group,disabled\n";
        $csv .= ",100,200,A,1,Seat A,desc,cls,0,0\n";

        $plan->import($csv, true);

        // existing seat should have been removed and new seat created
        $this->assertDatabaseMissing('seats', ['id' => $existing->id]);
        $this->assertDatabaseCount('seats', 1);
    }
}
