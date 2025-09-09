<?php

namespace Tests\Unit\app\Http\Requests;

use App\Http\Requests\TicketTransferRequest;
use App\Models\Event;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTransferRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketTransferRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new TicketTransferRequest();
        $this->assertIsArray($request->rules());
    }

    public function testCodeRuleFailsWhenCodeInvalid()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = new TicketTransferRequest();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            $this->assertEquals('The transfer code is invalid', $message);
        };
        $closure->call($request, 'code', 'NOPE', $fail);
        $this->assertTrue($called, 'Fail closure was not called for invalid transfer code');
    }

    public function testCodeRuleFailsWhenTicketCannotTransfer()
    {
        $owner = User::factory()->create();
        $requester = User::factory()->create();
        $this->actingAs($requester);

        // Event ended in the past -> canTransfer() will be false
        $event = Event::factory()->create(['ends_at' => Carbon::now()->subDay(), 'draft' => false]);
        $ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'transfer_code' => 'ABCD-EFGH-IJKL-MNOP'
        ]);

        $request = new TicketTransferRequest();
        $request->setUserResolver(function () use ($requester) {
            return $requester;
        });
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            $this->assertEquals('It is not possible to transfer this ticket', $message);
        };
        $closure->call($request, 'code', $ticket->transfer_code, $fail);
        $this->assertTrue($called, 'Fail closure was not called for non-transferable ticket');
    }

    public function testCodeRuleFailsWhenUserAlreadyOwnsTicket()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $event = Event::factory()->create(['ends_at' => Carbon::now()->addDay(), 'draft' => false]);
        $ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'transfer_code' => 'OWNR-CODE1-2345-6789'
        ]);

        $request = new TicketTransferRequest();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            $this->assertEquals('You already have the ticket in your account', $message);
        };
        $closure->call($request, 'code', $ticket->transfer_code, $fail);
        $this->assertTrue($called, 'Fail closure was not called when user already owns ticket');
    }

    public function testCodeRulePassesForValidTransferAndAdminBypassesDraft()
    {
        $admin = User::factory()->create();
        // give admin role via roles relation if roles exist; for simplicity, mock hasRole via attaching a Role model
        // If Role setup isn't available, set a flag on user and rely on hasRole checking roles() relation; to keep test stable, attach a role named 'admin' if possible
        if (class_exists(Role::class)) {
            $role = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
            $admin->roles()->attach($role);
        }
        $this->actingAs($admin);

        // Event is draft true; admin should bypass the draft filter
        $event = Event::factory()->create(['ends_at' => Carbon::now()->addDay(), 'draft' => true]);
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'user_id' => $owner->id,
            'transfer_code' => 'GOOD-CODE-0000-1111'
        ]);

        $request = new TicketTransferRequest();
        $request->setUserResolver(function () use ($admin) {
            return $admin;
        });
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
        };
        $closure->call($request, 'code', $ticket->transfer_code, $fail);
        $this->assertFalse($called, 'Fail closure was called for a valid transfer when admin should bypass draft filter');
    }
}
