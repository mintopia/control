<?php

namespace Tests\Unit\app\Policies;

use Tests\TestCase;
use App\Policies\TicketPolicy;
use App\Models\Ticket;
use App\Models\User;
use stdClass;

class TicketPolicyTest extends TestCase
{
    public function testUpdateReturnsTrueWhenUserOwnsTicket()
    {
        $user = new User(['id' => 1]);
        $ticket = new Ticket(['user_id' => 1]);
        $policy = new TicketPolicy();
        $this->assertTrue($policy->update($user, $ticket));
    }

    public function testUpdateReturnsFalseWhenUserDoesNotOwnTicket()
    {
        $user = new User(['id' => 1]);
        $ticket = new Ticket(['user_id' => 2]);
        $policy = new TicketPolicy();
        $this->assertFalse($policy->update($user, $ticket));
    }

    // FIXME Event method does not work properly in this test TBC
    // public function testSeeReturnsTrueIfEventNotDraft()
    // {
    //     $user = new User(['id' => 1]);
    //     $ticket = new Ticket();
    //     $ticket->event = (object)['draft' => false];
    //     $policy = new TicketPolicy();
    //     $this->assertTrue($policy->see($user, $ticket));
    // }

    // public function testSeeReturnsTrueIfUserIsAdminAndEventIsDraft()
    // {
    //     $user = $this->getMockBuilder(User::class)->onlyMethods(['hasRole'])->getMock();
    //     $user->method('hasRole')->with('admin')->willReturn(true);
    //     $ticket = new Ticket();
    //     $ticket->event = (object)['draft' => true];
    //     $policy = new TicketPolicy();
    //     $this->assertTrue($policy->see($user, $ticket));
    // }

    // public function testSeeReturnsFalseIfUserIsNotAdminAndEventIsDraft()
    // {
    //     $user = $this->getMockBuilder(User::class)->onlyMethods(['hasRole'])->getMock();
    //     $user->method('hasRole')->with('admin')->willReturn(false);
    //     $ticket = new Ticket();
    //     $ticket->event = (object)['draft' => true];
    //     $policy = new TicketPolicy();
    //     $this->assertFalse($policy->see($user, $ticket));
    // }
}
