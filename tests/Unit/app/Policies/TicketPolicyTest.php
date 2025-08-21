<?php

namespace Tests\Unit\app\Policies;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Policies\TicketPolicy;
use App\Models\Ticket;
use App\Models\User;
use stdClass;

class TicketPolicyTest extends TestCase
{
    use RefreshDatabase;
    
    public function testUpdateReturnsTrueWhenUserOwnsTicket()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->make(['user_id' => $user->id]);

        $policy = new TicketPolicy();
        $this->assertTrue($policy->update($user, $ticket));
    }

    public function testUpdateReturnsFalseWhenUserDoesNotOwnTicket()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->make(['user_id' => $user->id + 1]);

        $policy = new TicketPolicy();
        $this->assertFalse($policy->update($user, $ticket));
    }

    public function testSeeReturnsTrueIfEventNotDraft()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->make();
        $ticket->event = (object)['draft' => false];

        $policy = new TicketPolicy();
        $this->assertTrue($policy->see($user, $ticket));
    }

    public function testSeeReturnsTrueIfUserIsAdminAndEventIsDraft()
    {
        // Create a lightweight User subclass that returns true for hasRole('admin')
        $user = new class extends User {
            public function hasRole(string|\App\Models\Role $role): bool
            {
                if ($role instanceof \App\Models\Role) {
                    $role = $role->code;
                }
                return $role === 'admin';
            }
        };

        $ticket = Ticket::factory()->make();
        $ticket->event = (object)['draft' => true];

        $policy = new TicketPolicy();
        $this->assertTrue($policy->see($user, $ticket));
    }

    public function testSeeReturnsFalseIfUserIsNotAdminAndEventIsDraft()
    {
        // Create a lightweight User subclass that returns false for hasRole('admin')
        $user = new class extends User {
            public function hasRole(string|\App\Models\Role $role): bool
            {
                return false;
            }
        };

        $ticket = Ticket::factory()->make();
        $ticket->event = (object)['draft' => true];

        $policy = new TicketPolicy();
        $this->assertFalse($policy->see($user, $ticket));
    }
}
