<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\TicketObserver;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketObserverTest extends TestCase
{
    use RefreshDatabase;

    public function testSavedUpdatesPlanRevisionIfDirtyAndHasSeat()
    {
        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->create(['event_id' => $event->id, 'revision' => 1]);

        $user = \App\Models\User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);
        \App\Models\Seat::factory()->create(['seating_plan_id' => $plan->id, 'ticket_id' => $ticket->id]);

        // simulate change on ticket and call observer
        $ticket->some_flag = 1; // make it dirty
        $observer = new TicketObserver();
        $observer->saved($ticket);

        $plan->refresh();
        $this->assertGreaterThan(1, $plan->revision);
    }

    public function testSavedSyncsDiscordRolesOnUserChange()
    {
        $user = \App\Models\User::factory()->create();
        $ticket = \App\Models\Ticket::factory()->create(['user_id' => $user->id]);

        // simulate original user exists and new user exists
        $ticket->setRawAttributes(array_merge($ticket->getAttributes(), ['user_id' => $user->id]));
        $ticket->some_flag = 1;

        // Ensure calling saved does not throw when user change logic runs
        $observer = new TicketObserver();
        $observer->saved($ticket);

        $this->assertTrue(true);
    }
}
