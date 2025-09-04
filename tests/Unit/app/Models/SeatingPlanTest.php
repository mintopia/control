<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\SeatingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $plan->event());
    }

    public function testSeatsRelationship()
    {
        $plan = new SeatingPlan();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $plan->seats());
    }

    public function testBuildSortQueryReturnsBuilder()
    {
        $plan = new SeatingPlan();
        $plan->event_id = 1;
        $query = $plan->buildSortQuery();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }

    public function testUpdateRevisionIncrementsRevision()
    {
        $plan = $this->getMockBuilder(SeatingPlan::class)
            ->onlyMethods(['save'])
            ->getMock();
        $plan->revision = 1;
        $plan->expects($this->once())->method('save');
        $plan->updateRevision();
        $this->assertEquals(2, $plan->revision);
    }

    public function testToStringNameReturnsCode()
    {
        $plan = new SeatingPlan();
        $plan->code = 'test_code';
        $reflection = new \ReflectionClass($plan);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $this->assertEquals('test_code', $method->invoke($plan));
    }

    //VALIDATE Suggested Resolution:
    /**
     * Guard to ensure revision is only incremented once per request/operation.
     *
     * This prevents double increments when updateRevision() may be invoked
     * multiple times during complex operations (for example: import() and
     * a model observer both calling updateRevision()).
     */
    /* ADD To SeatingPlan.php
    protected bool $revisionUpdatedForCurrentOperation = false;

    public function updateRevision()
    {
        if ($this->revisionUpdatedForCurrentOperation) {
            return;
        }

        $this->revision = ($this->revision ?? 0) + 1;
        $this->revisionUpdatedForCurrentOperation = true;
        $this->save();
    }
    */
    public function testImportCreatesSeatsAndIncrementsRevision()
    {
        // Create a plan and ensure we start with revision 1
        $plan = \Database\Factories\SeatingPlanFactory::new()->create(['revision' => 1]);

        // CSV: ID,x,y,row,number,label,description,class,group,disabled
        $csv = "ID,x,y,row,number,label,description,class,group,disabled\n";
        $csv .= ",10,20,A,1,Front Left,desc,VIP,0,0\n";
        $csv .= ",11,21,A,2,Front Right,desc,VIP,0,0\n";

        $plan->import($csv, true);

        $this->assertDatabaseCount('seats', 2);
        $this->assertEquals(2, $plan->seats()->count());
        $this->assertEquals(2, $plan->revision);
    }

    public function testRandomiseAssignsTicketsToSeats()
    {
        // Create an event with a seating plan and seats
        $plan = \Database\Factories\SeatingPlanFactory::new()->create();
        $seats = \Database\Factories\SeatFactory::new()->count(3)->create(['seating_plan_id' => $plan->id]);

        // Create a ticket type with has_seat true and three tickets for the event
        $type = \Database\Factories\TicketTypeFactory::new()->create(['event_id' => $plan->event_id]);
        // Ensure the type has has_seat true
        $type->has_seat = true;
        $type->save();

        $tickets = \Database\Factories\TicketFactory::new()->count(3)->create([
            'event_id' => $plan->event_id,
            'ticket_type_id' => $type->id,
        ]);

        // Call randomise and assert tickets are assigned to seats
        $plan->randomise();

        $assigned = \App\Models\Seat::whereNotNull('ticket_id')->count();
        $this->assertEquals(3, $assigned);
        // Each ticket should be assigned to a seat
        foreach ($tickets as $ticket) {
            $this->assertDatabaseHas('seats', ['ticket_id' => $ticket->id]);
        }
    }
}
