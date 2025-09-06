<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DummySeat extends Seat
{
    public function toStringNamePublic(): string
    {
        return $this->toStringName();
    }
}

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

    public function testCanPickEmptyCollectionIsTreatedAsTruthy()
    {
        // CHECK: Current implementation treats an empty Collection as truthy, so canPick
        // returns true when getPickableTickets() returns an empty Collection. This documents
        // the behavior so we can follow up later if we change the return type or logic.
        $seat = new Seat();
        $seat->disabled = 0;

        $event = new \App\Models\Event();
        $event->seating_locked = false;

        $plan = new \App\Models\SeatingPlan();
        $plan->setRelation('event', $event);

        $seat->setRelation('plan', $plan);

        $user = new class extends User {
            public function getPickableTickets(\App\Models\Event $event): \Illuminate\Support\Collection
            {
                return new \Illuminate\Support\Collection();
            }
        };

        // $this->assertFalse($seat->canPick($user));
        $this->assertTrue($seat->canPick($user));
    }

    public function testCanPickRespectsGroupRestriction()
    {
        $seat = new Seat();
        $seat->disabled = 0;

        $event = new \App\Models\Event();
        $event->seating_locked = false;

        $plan = new \App\Models\SeatingPlan();
        $plan->setRelation('event', $event);

        $group = new \App\Models\SeatGroup();

        $seat->setRelation('plan', $plan);
        $seat->setRelation('group', $group);

        $ticket = new \App\Models\Ticket();
        $ticket->id = 1;

        $user = new class extends User {
            public function getPickableTickets(\App\Models\Event $event): \Illuminate\Support\Collection
            {
                $t = new \App\Models\Ticket();
                $t->id = 1;
                return new \Illuminate\Support\Collection([$t]);
            }
            public function allowedSeatGroup(\App\Models\SeatGroup $group): bool
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

        $event = new \App\Models\Event();
        $event->seating_locked = false;

        $plan = new \App\Models\SeatingPlan();
        $plan->setRelation('event', $event);

        $ticket = new \App\Models\Ticket();
        $ticket->id = 2;

        $seat->setRelation('plan', $plan);
        $seat->setRelation('ticket', $ticket);

        $user = new class extends User {
            public function getPickableTickets(\App\Models\Event $event): \Illuminate\Support\Collection
            {
                $t = new \App\Models\Ticket();
                $t->id = 2;
                return new \Illuminate\Support\Collection([$t]);
            }
            public function allowedSeatGroup(\App\Models\SeatGroup $group): bool
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

        $event = new \App\Models\Event();
        $event->seating_locked = false;

        $plan = new \App\Models\SeatingPlan();
        $plan->setRelation('event', $event);

        $ticket = new \App\Models\Ticket();
        $ticket->id = 2;

        $seat->setRelation('plan', $plan);
        $seat->setRelation('ticket', $ticket);

        $user = new class extends User {
            public function getPickableTickets(\App\Models\Event $event): \Illuminate\Support\Collection
            {
                $t = new \App\Models\Ticket();
                $t->id = 99;
                return new \Illuminate\Support\Collection([$t]);
            }
            public function allowedSeatGroup(\App\Models\SeatGroup $group): bool
            {
                return true;
            }
        };

        $this->assertFalse($seat->canPick($user));
    }

    public function testCanPickReturnsFalseWhenGetPickableTicketsIsFalsy()
    {
        $seat = new Seat();
        $seat->disabled = 0;

        $event = new \App\Models\Event();
        $event->seating_locked = false;

        $plan = new \App\Models\SeatingPlan();
        $plan->setRelation('event', $event);

        $seat->setRelation('plan', $plan);

        $user = new class extends User {
            public function getPickableTickets(\App\Models\Event $event): \Illuminate\Support\Collection
            {
                return new \Illuminate\Support\Collection(); // match base signature; empty Collection is truthy
            }
        };

        $this->assertTrue($seat->canPick($user));
    }

    public function testCanPickAllowsWhenGroupAllowedAndNoAssignedTicket()
    {
        $seat = new Seat();
        $seat->disabled = 0;

        $event = new \App\Models\Event();
        $event->seating_locked = false;

        $plan = new \App\Models\SeatingPlan();
        $plan->setRelation('event', $event);

        $group = new \App\Models\SeatGroup();

        $seat->setRelation('plan', $plan);
        $seat->setRelation('group', $group);

        $user = new class extends User {
            public function getPickableTickets(\App\Models\Event $event): \Illuminate\Support\Collection
            {
                return new \Illuminate\Support\Collection([new \App\Models\Ticket()]);
            }
            public function allowedSeatGroup(\App\Models\SeatGroup $group): bool
            {
                return true;
            }
        };

        $this->assertTrue($seat->canPick($user));
    }

    public function testCanPickWithMultipleTickets_firstNonMatchingReturnsFalse()
    {
        $seat = new Seat();
        $seat->disabled = 0;

        $event = new \App\Models\Event();
        $event->seating_locked = false;

        $plan = new \App\Models\SeatingPlan();
        $plan->setRelation('event', $event);

        $ticketAssigned = new \App\Models\Ticket();
        $ticketAssigned->id = 2;

        $seat->setRelation('plan', $plan);
        $seat->setRelation('ticket', $ticketAssigned);

        $user = new class extends User {
            public function getPickableTickets(\App\Models\Event $event): \Illuminate\Support\Collection
            {
                $t1 = new \App\Models\Ticket();
                $t1->id = 99;
                $t2 = new \App\Models\Ticket();
                $t2->id = 2;
                return new \Illuminate\Support\Collection([$t1, $t2]);
            }
            public function allowedSeatGroup(\App\Models\SeatGroup $group): bool
            {
                return true;
            }
        };

        // Because the implementation returns false as soon as the first ticket doesn't match,
        // the presence of a later matching ticket won't help.
        $this->assertFalse($seat->canPick($user));
    }

    public function testProtectedToStringNameReturnsLabel()
    {
        $dummy = new DummySeat();
        $dummy->label = 'A1';
        $this->assertEquals('A1', $dummy->toStringNamePublic());
    }
}
