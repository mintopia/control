<?php

namespace Tests\Unit\app\Models;

use App\Models\Seat;
use App\Models\SeatingPlan;
use Database\Factories\SeatFactory;
use Database\Factories\SeatingPlanFactory;
use Database\Factories\TicketFactory;
use Database\Factories\TicketTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class SeatingPlanTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateSeatingPlan()
    {
        $plan = new SeatingPlan();
        $this->assertInstanceOf(SeatingPlan::class, $plan);
    }

    public function testEventRelationship()
    {
        $plan = new SeatingPlan();
        $this->assertInstanceOf(BelongsTo::class, $plan->event());
    }

    public function testSeatsRelationship()
    {
        $plan = new SeatingPlan();
        $this->assertInstanceOf(HasMany::class, $plan->seats());
    }

    public function testBuildSortQueryReturnsBuilder()
    {
        $plan = new SeatingPlan();
        $plan->event_id = 1;
        $query = $plan->buildSortQuery();
        $this->assertInstanceOf(Builder::class, $query);
    }

    public function testUpdateRevisionIncrementsRevision()
    {
        // Use a real model persisted to the test database so we can assert the revision
        $plan = SeatingPlanFactory::new()->create(['revision' => 1]);

        $plan->updateRevision();

        // Refresh from DB to ensure the change was persisted
        $plan->refresh();
        // SeatingPlanObserver increments revision during save, resulting in a total increment of 2
        $this->assertEquals(3, $plan->revision);
    }

    public function testToStringNameReturnsCode()
    {
        $plan = new SeatingPlan();
        $plan->code = 'test_code';
        $reflection = new ReflectionClass($plan);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $this->assertEquals('test_code', $method->invoke($plan));
    }

    //CHECK behaviour for revision updates, this may be unintended behaviour here
    public function testImportUpdatesRevision()
    {
        // Create a plan and ensure we start with revision 1
        $plan = SeatingPlanFactory::new()->create(['revision' => 1]);

        // CSV: ID,x,y,row,number,label,description,class,group,disabled
        $csv = "ID,x,y,row,number,label,description,class,group,disabled\n";
        $csv .= ",10,20,A,1,Front Left,desc,VIP,0,0\n";
        $csv .= ",11,21,A,2,Front Right,desc,VIP,0,0\n";

        $plan->import($csv);

        // Observer increments during save, final revision will be 3
        $this->assertEquals(3, $plan->revision);
    }

    public function testImportCreatesSeatsAndIncrementsRevision()
    {
        // Create a plan and ensure we start with revision 1
        $plan = SeatingPlanFactory::new()->create(['revision' => 1]);

        // CSV: ID,x,y,row,number,label,description,class,group,disabled
        $csv = "ID,x,y,row,number,label,description,class,group,disabled\n";
        $csv .= ",10,20,A,1,Front Left,desc,VIP,0,0\n";
        $csv .= ",11,21,A,2,Front Right,desc,VIP,0,0\n";

        $plan->import($csv, true);

        $this->assertDatabaseCount('seats', 2);
        $this->assertEquals(2, $plan->seats()->count());
        // Observer increments during save, final revision will be 3
        $this->assertEquals(3, $plan->revision);
    }

    public function testRandomiseAssignsTicketsToSeats()
    {
        // Create an event with a seating plan and seats
        $plan = SeatingPlanFactory::new()->create();
        $seats = SeatFactory::new()->count(3)->create(['seating_plan_id' => $plan->id]);

        // Create a ticket type with has_seat true and three tickets for the event
        $type = TicketTypeFactory::new()->create(['event_id' => $plan->event_id]);
        // Ensure the type has has_seat true
        $type->has_seat = true;
        $type->save();

        $tickets = TicketFactory::new()->count(3)->create([
            'event_id' => $plan->event_id,
            'ticket_type_id' => $type->id,
        ]);

        // Call randomise and assert tickets are assigned to seats
        $plan->randomise();

        $assigned = Seat::whereNotNull('ticket_id')->count();
        $this->assertEquals(3, $assigned);
        // Each ticket should be assigned to a seat
        foreach ($tickets as $ticket) {
            $this->assertDatabaseHas('seats', ['ticket_id' => $ticket->id]);
        }
    }

    public function testImportSkipsRowsWithEmptyLabel()
    {
        // Create a plan and ensure we start with revision 1
        $plan = SeatingPlanFactory::new()->create(['revision' => 1]);

        // CSV: ID,x,y,row,number,label,description,class,group,disabled
        $csv = "ID,x,y,row,number,label,description,class,group,disabled\n";
        // Row with empty label (index 5) should be skipped by import()
        $csv .= ",10,20,A,1,,desc,VIP,0,0\n";

        $plan->import($csv, true);

        // No seats should have been created
        $this->assertDatabaseCount('seats', 0);
        $this->assertEquals(0, $plan->seats()->count());

        // Observer increments during save; starting from 1 expect final revision 3
        $plan->refresh();
        $this->assertEquals(3, $plan->revision);
    }

    public function testImportWithNonNumericIdCreatesSeat()
    {
        // Create a plan and ensure we start with revision 1
        $plan = SeatingPlanFactory::new()->create(['revision' => 1]);

        // CSV: ID,x,y,row,number,label,description,class,group,disabled
        $csv = "ID,x,y,row,number,label,description,class,group,disabled\n";
        // Non-numeric ID 'abc' should be ignored by the numeric check and treated as a new seat
        $csv .= "abc,15,25,B,1,SeatLabel,desc,VIP,0,0\n";

        $plan->import($csv, true);

        $this->assertDatabaseCount('seats', 1);
        $this->assertEquals(1, $plan->seats()->count());

        $plan->refresh();
        // Observer increments during save; starting from 1 expect final revision 3
        $this->assertEquals(3, $plan->revision);
    }

    public function testImportUpdatesExistingSeatWhenValidIdProvided()
    {
        // Create a plan and an existing seat under that plan
        $plan = SeatingPlanFactory::new()->create(['revision' => 1]);
        $seat = SeatFactory::new()->create([
            'seating_plan_id' => $plan->id,
            'label' => 'OLD_LABEL',
            'x' => 1,
            'y' => 1,
            'row' => 'Z',
            'number' => 99,
        ]);

        // CSV: ID,x,y,row,number,label,description,class,group,disabled
        $csv = "ID,x,y,row,number,label,description,class,group,disabled\n";
        // Use the existing seat ID in the CSV so import() should find and update it
        $csv .= "{$seat->id},10,20,A,1,Front Updated,desc,VIP,0,0\n";

        // Call import without wiping so existing seats remain available for lookup
        $plan->import($csv);

        // Refresh the seat and assert it was updated
        $seat->refresh();
        $this->assertEquals('Front Updated', $seat->label);
        $this->assertEquals(10, $seat->x);
        $this->assertEquals(20, $seat->y);
        $this->assertEquals('A', $seat->row);
        $this->assertEquals(1, $seat->number);

        // No new seats should have been created
        $this->assertDatabaseCount('seats', 1);

        // Plan revision should have been incremented by the import call
        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }
}
