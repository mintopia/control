<?php

namespace Tests\Unit\app\Models;

use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatGroup;
use App\Models\SeatingPlan;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SeatTest extends TestCase
{
    public function testCanInstantiateSeat()
    {
        $seat = new Seat();
        $this->assertInstanceOf(Seat::class, $seat);
    }

    public function testRelationshipsReturnBelongsTo()
    {
        $seat = new Seat();
        $this->assertInstanceOf(BelongsTo::class, $seat->ticket());
        $this->assertInstanceOf(BelongsTo::class, $seat->plan());
        $this->assertInstanceOf(BelongsTo::class, $seat->group());
    }

    public function testCanPickReturnsFalseWhenDisabled()
    {
        $seat = new Seat();
        $seat->disabled = 1;
        $seat->setRelation('plan', (object)['event' => (object)['seating_locked' => false]]);
        $this->assertFalse($seat->canPick(null));
    }

    public function testCanPickReturnsFalseWhenSeatingLocked()
    {
        $seat = new Seat();
        $seat->disabled = 0;
        $seat->setRelation('plan', (object)['event' => (object)['seating_locked' => true]]);
        $this->assertFalse($seat->canPick(null));
    }

    public function testCanPickReturnsTrueForNullUserWhenNotLockedAndNotDisabled()
    {
        $seat = new Seat();
        $seat->disabled = 0;
        $seat->setRelation('plan', (object)['event' => (object)['seating_locked' => false]]);
        $this->assertTrue($seat->canPick(null));
    }

    // VALIDATE Empty Collection was truthy, so false never was returned.
    public function testCanPickReturnsFalseWhenGetPickableTicketsIsFalse()
    {
        $seat = new Seat();
        $seat->disabled = 0;

        $event = new Event();
        $event->seating_locked = false;

        $plan = new SeatingPlan();
        $plan->setRelation('event', $event);

        $seat->setRelation('plan', $plan);

        $user = new class extends User {
            public function getPickableTickets(Event $event): Collection
            {
                return new Collection();
            }
        };

        $this->assertFalse($seat->canPick($user));
    }

    public function testCanPickRespectsGroupRestriction()
    {
        $seat = new Seat();
        $seat->disabled = 0;

        $event = new Event();
        $event->seating_locked = false;

        $plan = new SeatingPlan();
        $plan->setRelation('event', $event);

        $group = new SeatGroup();

        $seat->setRelation('plan', $plan);
        $seat->setRelation('group', $group);

        $ticket = new Ticket();
        $ticket->id = 1;

        $user = new class extends User {
            public function getPickableTickets(Event $event): Collection
            {
                $t = new Ticket();
                $t->id = 1;
                return new Collection([$t]);
            }

            public function allowedSeatGroup(SeatGroup $group): bool
            {
                return false;
            }
        };

        $this->assertFalse($seat->canPick($user));
    }

    public function testCanPickReturnsTrueWhenUserHasMatchingTicket()
    {
        $seat = new Seat();
        $seat->disabled = 0;

        $event = new Event();
        $event->seating_locked = false;

        $plan = new SeatingPlan();
        $plan->setRelation('event', $event);

        $ticket = new Ticket();
        $ticket->id = 2;

        $seat->setRelation('plan', $plan);
        $seat->setRelation('ticket', $ticket);

        $user = new class extends User {
            public function getPickableTickets(Event $event): Collection
            {
                $t = new Ticket();
                $t->id = 2;
                return new Collection([$t]);
            }

            public function allowedSeatGroup(SeatGroup $group): bool
            {
                return true;
            }
        };

        $this->assertTrue($seat->canPick($user));
    }

    public function testCanPickReturnsFalseWhenUserTicketsDoNotMatchAssignedTicket()
    {
        $seat = new Seat();
        $seat->disabled = 0;

        $event = new Event();
        $event->seating_locked = false;

        $plan = new SeatingPlan();
        $plan->setRelation('event', $event);

        $ticket = new Ticket();
        $ticket->id = 2;

        $seat->setRelation('plan', $plan);
        $seat->setRelation('ticket', $ticket);

        $user = new class extends User {
            public function getPickableTickets(Event $event): Collection
            {
                $t = new Ticket();
                $t->id = 99;
                return new Collection([$t]);
            }

            public function allowedSeatGroup(SeatGroup $group): bool
            {
                return true;
            }
        };

        $this->assertFalse($seat->canPick($user));
    }

    public function testCanPickAllowsWhenGroupAllowedAndNoAssignedTicket()
    {
        $seat = new Seat();
        $seat->disabled = 0;

        $event = new Event();
        $event->seating_locked = false;

        $plan = new SeatingPlan();
        $plan->setRelation('event', $event);

        $group = new SeatGroup();

        $seat->setRelation('plan', $plan);
        $seat->setRelation('group', $group);

        $user = new class extends User {
            public function getPickableTickets(Event $event): Collection
            {
                return new Collection([new Ticket()]);
            }

            public function allowedSeatGroup(SeatGroup $group): bool
            {
                return true;
            }
        };

        $this->assertTrue($seat->canPick($user));
    }

    public function testCanPickWithMultipleTicketsFirstNonMatchingReturnsFalse()
    {
        $seat = new Seat();
        $seat->disabled = 0;

        $event = new Event();
        $event->seating_locked = false;

        $plan = new SeatingPlan();
        $plan->setRelation('event', $event);

        $ticketAssigned = new Ticket();
        $ticketAssigned->id = 2;

        $seat->setRelation('plan', $plan);
        $seat->setRelation('ticket', $ticketAssigned);

        $user = new class extends User {
            public function getPickableTickets(Event $event): Collection
            {
                $t1 = new Ticket();
                $t1->id = 99;
                $t2 = new Ticket();
                $t2->id = 2;
                return new Collection([$t1, $t2]);
            }

            public function allowedSeatGroup(SeatGroup $group): bool
            {
                return true;
            }
        };

        // Because the implementation returns false as soon as the first ticket doesn't match,
        // the presence of a later matching ticket won't help.
        $this->assertFalse($seat->canPick($user));
    }

    public function testProtectedFunctionToStringName()
    {
        $seat = new Seat();
        $seat->label = 'A1';

        $reflection = new \ReflectionClass($seat);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $result = $method->invoke($seat);

        $this->assertEquals($seat->label, $result);
    }
}
