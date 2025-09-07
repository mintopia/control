<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\UserController;
use App\Http\Requests\Admin\DeleteRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\EmailAddress;
use App\Models\Event;
use App\Models\Role;
use App\Models\Seat;
use App\Models\SeatingPlan;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsViewWithFilters()
    {
        User::factory()->create(['nickname' => 'alpha']);
        User::factory()->create(['nickname' => 'beta']);

        $controller = new UserController();
        $req = Request::create('/', 'GET', ['name' => 'alph', 'order' => 'name', 'order_direction' => 'desc']);
        $resp = $controller->index($req);
        $this->assertTrue(is_object($resp));
    }

    public function testIndexFiltersByIdAndNicknameAndEmailAndOrderCases()
    {
        $u1 = User::factory()->create(['nickname' => 'alphax', 'name' => 'Alice']);
        $u2 = User::factory()->create(['nickname' => 'betax', 'name' => 'Bob']);
        $email = EmailAddress::create(['user_id' => $u1->id, 'email' => 'alice@example.test']);

        $controller = new UserController();

        // filter by id
        $req = Request::create('/', 'GET', ['id' => $u1->id]);
        $resp = $controller->index($req);
        $this->assertTrue(is_object($resp));

        // filter by nickname
        $req2 = Request::create('/', 'GET', ['nickname' => 'alph']);
        $resp2 = $controller->index($req2);
        $this->assertTrue(is_object($resp2));

        // filter by email
        $req3 = Request::create('/', 'GET', ['email' => 'alice@']);
        $resp3 = $controller->index($req3);
        $this->assertTrue(is_object($resp3));

        // order cases: nickname and default id
        $r4 = Request::create('/', 'GET', ['order' => 'nickname', 'order_direction' => 'asc']);
        $this->assertTrue(is_object($controller->index($r4)));
        $r5 = Request::create('/', 'GET', ['order' => 'id', 'order_direction' => 'desc']);
        $this->assertTrue(is_object($controller->index($r5)));
    }

    public function testShowEditDeleteReturnViews()
    {
        $user = User::factory()->create();
        $role = Role::create(['code' => 'r1', 'name' => 'R1']);
        $user->roles()->attach($role);

        $controller = new UserController();
        $this->assertTrue(is_object($controller->show($user)));
        $this->assertTrue(is_object($controller->edit($user)));
        $this->assertTrue(is_object($controller->delete($user)));
    }

    public function testUpdateSetsFieldsAndRoles()
    {
        $user = User::factory()->create(['nickname' => 'oldnick', 'name' => 'Old']);
        $email = EmailAddress::create(['user_id' => $user->id, 'email' => 'u@example.test']);

        $r1 = Role::create(['code' => 'r1', 'name' => 'R1']);
        $r2 = Role::create(['code' => 'r2', 'name' => 'R2']);
        $user->roles()->attach($r1);

        $controller = new UserController();

        $payload = [
            'nickname' => 'newnick',
            'name' => 'New Name',
            'primary_email_id' => $email->id,
            'terms' => 1,
            'first_login' => 1,
            'suspended' => 1,
            'roles' => ['r2'],
        ];
        $req = UserUpdateRequest::create('/', 'POST', $payload);

        try {
            $controller->update($req, $user);
        } catch (UrlGenerationException $ex) {
            // ignore missing route
        }

        $fresh = $user->fresh();
        $this->assertEquals('newnick', $fresh->nickname);
        $this->assertEquals('New Name', $fresh->name);
        $this->assertEquals($email->id, $fresh->primary_email_id);
        $this->assertNotNull($fresh->terms_agreed_at);
        // first_login is saved as the inverse of the request value
        $this->assertEquals(0, $fresh->first_login);
        $this->assertEquals(1, $fresh->suspended);
        $this->assertTrue($fresh->roles()->whereCode('r2')->exists());
    }

    public function testUpdateHandlesTermsFalseAndNoPrimaryEmail()
    {
        $user = User::factory()->create(['nickname' => 'oldnick', 'name' => 'Old']);
        // no emails created; primary_email_id not provided

        $r1 = Role::create(['code' => 'r1', 'name' => 'R1']);
        $user->roles()->attach($r1);

        $controller = new UserController();

        $payload = [
            'nickname' => 'nn',
            'name' => 'NN',
            'terms' => 0,
            'first_login' => 0,
            'suspended' => 0,
            'roles' => [],
        ];
        $req = UserUpdateRequest::create('/', 'POST', $payload);
        try {
            $controller->update($req, $user);
        } catch (UrlGenerationException $ex) {
        }

        $fresh = $user->fresh();
        $this->assertNull($fresh->terms_agreed_at);
        $this->assertEquals(1, $fresh->first_login);
        $this->assertEquals(0, $fresh->suspended);
    }

    public function testDestroyDeletesUser()
    {
        $user = User::factory()->create();
        $controller = new UserController();
        $req = DeleteRequest::create('/', 'POST', []);
        try {
            $controller->destroy($req, $user);
        } catch (UrlGenerationException $ex) {
            // ignore redirect route
        }
        $this->assertNull(User::find($user->id));
    }

    public function testDestroyUpdatesSeatingPlanRevision()
    {
        // create event and seating plan and a seat with a ticket for the user
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->for($event)->create(['revision' => 1]);
        $type = TicketType::factory()->for($event)->create(['has_seat' => 1]);
        $ticket = Ticket::factory()->for($user)->for($type, 'type')->create();
        $seat = Seat::factory()->for($plan, 'plan')->create();
        // associate ticket with seat
        $seat->ticket()->associate($ticket);
        $seat->save();

        $controller = new UserController();
        $req = DeleteRequest::create('/', 'POST', []);
        try {
            $controller->destroy($req, $user);
        } catch (UrlGenerationException $ex) {
        }

        $this->assertNull(User::find($user->id));
        $this->assertGreaterThan(1, $plan->fresh()->revision);
    }

    public function testImpersonateStoresOriginalUserInSession()
    {
        $original = User::factory()->create();
        $target = User::factory()->create();

        $req = new HelperClasses\FakeRequest();
        $req->setUserResolver(function () use ($original) {
            return $original;
        });

        // Mock our session guard to return our user
        $mockGuard = $this->mock(SessionGuard::class);
        $mockGuard->shouldReceive('login')->andReturn($target);
        Auth::shouldReceive('guard')->with('web')->andReturn($mockGuard);

        $controller = new UserController();
        try {
            $controller->impersonate($req, $target);
        } catch (UrlGenerationException $ex) {
            // route may not exist, ignore
        }

        $this->assertEquals($original->id, $req->session()->get('originalUserId'));
        $this->assertEquals(true, $req->session()->get('impersonating'));
    }

    public function testSyncTicketsSetsTimestamp()
    {
        $user = User::factory()->create(['tickets_synced_at' => null]);
        $controller = new UserController();
        try {
            $controller->syncTickets($user);
        } catch (UrlGenerationException $ex) {
            // ignore redirect
        }
        $this->assertNotNull($user->fresh()->tickets_synced_at);
    }

    public function testUpdateWithNonexistentPrimaryEmailIdDoesNotAssociate()
    {
        $user = User::factory()->create(['nickname' => 'oldnick', 'name' => 'Old']);

        $r1 = Role::create(['code' => 'r1', 'name' => 'R1']);
        $user->roles()->attach($r1);

        $controller = new UserController();

        // Provide a primary_email_id that does not exist
        $payload = [
            'nickname' => 'nn',
            'name' => 'NN',
            'primary_email_id' => 999999,
            'terms' => 0,
            'first_login' => 0,
            'suspended' => 0,
            'roles' => [],
        ];
        $req = UserUpdateRequest::create('/', 'POST', $payload);
        try {
            $controller->update($req, $user);
        } catch (UrlGenerationException $ex) {
        }

        $fresh = $user->fresh();
        // primary_email_id should remain null because the provided id didn't match any email
        $this->assertNull($fresh->primary_email_id);
    }

    public function testUpdateDoesNotOverwriteExistingTermsAgreedAtWhenTermsTrue()
    {
        $user = User::factory()->create(['nickname' => 'oldnick', 'name' => 'Old']);
        // seed an existing terms_agreed_at timestamp
        $past = Carbon::now()->subDays(5);
        $user->terms_agreed_at = $past;
        $user->save();

        $controller = new UserController();

        $payload = [
            'nickname' => 'nn',
            'name' => 'NN',
            'terms' => 1,
            'first_login' => 0,
            'suspended' => 0,
            'roles' => [],
        ];
        $req = UserUpdateRequest::create('/', 'POST', $payload);
        try {
            $controller->update($req, $user);
        } catch (UrlGenerationException $ex) {
        }

        $fresh = $user->fresh();
        $this->assertEquals($past->timestamp, $fresh->terms_agreed_at->timestamp);
    }

    public function testUpdateHandlesRoleDetachAndAttach()
    {
        $user = User::factory()->create(['nickname' => 'oldnick', 'name' => 'Old']);

        $r1 = Role::create(['code' => 'r1', 'name' => 'R1']);
        $r2 = Role::create(['code' => 'r2', 'name' => 'R2']);
        $r3 = Role::create(['code' => 'r3', 'name' => 'R3']);
        // user initially has r1 and r2
        $user->roles()->attach([$r1->id, $r2->id]);

        $controller = new UserController();

        // request wants only r3 (should detach r1 and r2, then attach r3)
        $payload = [
            'nickname' => 'nn',
            'name' => 'NN',
            'terms' => 0,
            'first_login' => 0,
            'suspended' => 0,
            'roles' => ['r3'],
        ];
        $req = UserUpdateRequest::create('/', 'POST', $payload);
        try {
            $controller->update($req, $user);
        } catch (UrlGenerationException $ex) {
            // ignore
        }

        $fresh = $user->fresh();
        $this->assertFalse($fresh->roles()->whereCode('r1')->exists());
        $this->assertFalse($fresh->roles()->whereCode('r2')->exists());
        $this->assertTrue($fresh->roles()->whereCode('r3')->exists());
    }

    public function testUpdateSetsTermsAgreedAtWhenPreviouslyNull()
    {
        $user = User::factory()->create(['terms_agreed_at' => null]);

        $controller = new UserController();

        $payload = [
            'nickname' => $user->nickname,
            'name' => $user->name,
            'terms' => 1,
            'first_login' => 0,
            'suspended' => 0,
            'roles' => [],
        ];
        $req = UserUpdateRequest::create('/', 'POST', $payload);
        try {
            $controller->update($req, $user);
        } catch (UrlGenerationException $ex) {
            // ignore
        }

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->terms_agreed_at);
        $this->assertInstanceOf(Carbon::class, $fresh->terms_agreed_at);
        // timestamp should be very recent
        $this->assertLessThanOrEqual(5, now()->diffInSeconds($fresh->terms_agreed_at));
    }

    public function testUpdateKeepsExistingRoleAndDoesNotDuplicate()
    {
        $user = User::factory()->create();
        $r1 = Role::create(['code' => 'keep', 'name' => 'Keep']);
        // attach existing role
        $user->roles()->attach($r1->id);

        $controller = new UserController();

        $payload = [
            'nickname' => $user->nickname,
            'name' => $user->name,
            'terms' => 0,
            'first_login' => 0,
            'suspended' => 0,
            'roles' => ['keep'],
        ];
        $req = UserUpdateRequest::create('/', 'POST', $payload);
        try {
            $controller->update($req, $user);
        } catch (UrlGenerationException $ex) {
            // ignore
        }

        $fresh = $user->fresh();
        // role should still be attached and not duplicated
        $this->assertTrue($fresh->roles()->whereCode('keep')->exists());
        $this->assertCount(1, $fresh->roles);
    }
}
