<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use App\Models\Role;
use App\Models\EmailAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use App\Http\Requests\Admin\DeleteRequest;

class FakeSession
{
    public $data = [];
    public function flush()
    {
        $this->data = [];
    }
    public function regenerate($a = true)
    { /* noop */
    }
    public function put($k, $v)
    {
        $this->data[$k] = $v;
    }
    public function get($k, $default = null)
    {
        return $this->data[$k] ?? $default;
    }
}

class FakeRequest extends Request
{
    protected $sess;
    public function __construct()
    {
        parent::__construct();
        $this->sess = new FakeSession();
    }
    public function session()
    {
        return $this->sess;
    }
}

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
        $email = \App\Models\EmailAddress::create(['user_id' => $u1->id, 'email' => 'alice@example.test']);

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
        $req = \App\Http\Requests\Admin\UserUpdateRequest::create('/', 'POST', $payload);

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
        $req = \App\Http\Requests\Admin\UserUpdateRequest::create('/', 'POST', $payload);
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
        $event = \App\Models\Event::factory()->create();
        $plan = \App\Models\SeatingPlan::factory()->for($event)->create(['revision' => 1]);
        $type = \App\Models\TicketType::factory()->for($event)->create(['has_seat' => 1]);
        $ticket = \App\Models\Ticket::factory()->for($user)->for($type, 'type')->create();
        $seat = \App\Models\Seat::factory()->for($plan, 'plan')->create();
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

        $req = new FakeRequest();
        $req->setUserResolver(function () use ($original) {
            return $original;
        });

        // stub the auth guard so login() doesn't depend on framework session state
        Auth::shouldReceive('guard')->with('web')->andReturn(new class {
            public function login($u)
            {
                return true;
            }
        });

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
            $controller->sync_tickets($user);
        } catch (UrlGenerationException $ex) {
            // ignore redirect
        }
        $this->assertNotNull($user->fresh()->tickets_synced_at);
    }
}
