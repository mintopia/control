<?php

namespace Tests\Unit\app\Http\Controllers;

use App\Http\Controllers\SeatingPlanController;
use App\Models\Clan;
use App\Models\ClanRole;
use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatGroup;
use App\Models\SeatGroupAssignment;
use App\Models\SeatingPlan;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Database\Factories\ClanMembershipFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

// Event facade previously used here was unnecessary; use the model saved callback instead

class SeatingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new SeatingPlanController();
        $this->assertInstanceOf(SeatingPlanController::class, $controller);
    }

    public function testIndexReturnsView()
    {
        $user = User::factory()->create();
        Auth::shouldReceive('user')->andReturn($user);
        $request = new Request();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $request->setLaravelSession(app('session.store'));
        $controller = new SeatingPlanController();
        $response = $controller->index($request);
        $this->assertTrue(is_object($response));
    }

    public function testIndexShowsDraftsToAdminOrManager()
    {
        // Create two events, one draft and one not
        $draftEvent = Event::factory()->create(['draft' => true, 'starts_at' => now()->addDays(2)]);
        $publishedEvent = Event::factory()->create(['draft' => false, 'starts_at' => now()->addDay()]);

        // Create a user mock that reports having admin/manager roles
        $userMock = $this->createMock(User::class);
        $userMock->method('hasAnyRole')->willReturn(true);

        $request = new Request();
        $request->setUserResolver(function () use ($userMock) {
            return $userMock;
        });
        $request->setLaravelSession(app('session.store'));

        $controller = new SeatingPlanController();
        $view = $controller->index($request);

        $this->assertTrue(is_object($view));
        $data = $view->getData();
        $this->assertArrayHasKey('events', $data);
        $eventsPaginator = $data['events'];
        $ids = array_map(fn($e) => $e->id, $eventsPaginator->items());
        $this->assertContains($draftEvent->id, $ids);
        $this->assertContains($publishedEvent->id, $ids);
    }

    public function testShowReturnsView()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $request = new Request();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $request->setLaravelSession(app('session.store'));
        $controller = new SeatingPlanController();
        $response = $controller->show($request, $event);
        $this->assertTrue(is_object($response));
    }

    public function testShowCoversLoopsAndAjaxPlan()
    {
        $user = User::factory()->create(['nickname' => 'alpha']);
        $other = User::factory()->create(['nickname' => 'beta']);

        // create clan role and membership so loops detect seat managers
        ClanRole::factory()->create(['code' => 'leader']);
        $clan = Clan::factory()->create();
        ClanMembershipFactory::new()->create(['clan_id' => $clan->id, 'user_id' => $user->id, 'clan_role_id' => 1]);
        ClanMembershipFactory::new()->create(['clan_id' => $clan->id, 'user_id' => $other->id, 'clan_role_id' => 1]);

        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id, 'code' => 'P1']);
        $plan2 = SeatingPlan::factory()->create(['event_id' => $event->id, 'code' => 'P2']);

        $type = TicketType::factory()->create(['has_seat' => true]);

        // ticket belonging to current user with seat
        $ticket1 = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $type->id]);
        $seat1 = Seat::factory()->create(['seating_plan_id' => $plan->id]);
        $seat1->ticket()->associate($ticket1);
        $seat1->save();

        // ticket belonging to other user also with seat
        $ticket2 = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $other->id, 'ticket_type_id' => $type->id]);
        $seat2 = Seat::factory()->create(['seating_plan_id' => $plan->id]);
        $seat2->ticket()->associate($ticket2);
        $seat2->save();

        $request = Request::create('/seating', 'GET', ['plan' => $plan->code]);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setUserResolver(fn() => $user);
        $request->setLaravelSession(app('session.store'));

        $controller = new SeatingPlanController();
        $view = $controller->show($request, $event, $ticket1);

        $this->assertTrue(is_object($view));
        $data = $view->getData();
        $this->assertArrayHasKey('plan', $data);
        $this->assertEquals($plan->id, $data['plan']->id);
        $this->assertArrayHasKey('responsibleSeats', $data);
        $this->assertArrayHasKey('mySeats', $data);
    }

    public function testSelectAssignsSeat()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id]);
        $type = TicketType::factory()->create(['has_seat' => true]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $type->id]);

        $request = Request::create('/select', 'POST');
        $request->setUserResolver(fn() => $user);

        $controller = new SeatingPlanController();
        $response = $controller->select($request, $event, $ticket, $seat);

        $this->assertEquals($ticket->id, $seat->fresh()->ticket_id);
    }

    public function testUnseatRemovesTicketFromSeat()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id]);
        $type = TicketType::factory()->create(['has_seat' => true]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $type->id]);

        $seat->ticket()->associate($ticket);
        $seat->save();

        // ensure relations are present on the ticket so controller sees them
        $ticket->setRelation('seat', $seat);
        $seat->setRelation('plan', $plan);

        $request = Request::create('/unseat', 'POST');
        $request->setUserResolver(fn() => $user);

        // reload relations so helpers evaluate correctly
        $ticket->load('type', 'event', 'user');
        $this->assertTrue($ticket->canPickSeat(), 'Ticket should be pickable in test setup');
        $this->assertTrue($ticket->canBeManagedBy($user), 'User should be able to manage their own ticket');

        // ensure the seat was associated
        $this->assertEquals($ticket->id, $seat->fresh()->ticket_id);

        $controller = new SeatingPlanController();
        $response = $controller->unseat($request, $event, $ticket);

        $this->assertNull($seat->fresh()->ticket_id);
    }

    public function testUnseatRedirectsWhenNotAuthorized()
    {
        $viewer = User::factory()->create();
        $owner = User::factory()->create();

        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $type = TicketType::factory()->create(['has_seat' => true]);

        // Ticket owned by someone else and viewer cannot manage it
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $owner->id, 'ticket_type_id' => $type->id]);

        // Register route so redirectToRoute can build URL in test
        $this->app['router']->get('/seating/{code}/{id?}', fn() => 'ok')->name('seatingplans.show');

        $request = Request::create('/unseat', 'POST');
        $request->setUserResolver(fn() => $viewer);
        $request->setLaravelSession(app('session.store'));

        $controller = new SeatingPlanController();
        $resp = $controller->unseat($request, $event, $ticket);

        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertNotEmpty($resp->getSession()->get('errorMessage'));
    }

    //VALIDATE Fix in SeatingPlanController allows this one to succeed
    public function testUnseatRedirectsWhenNoSeatPresent()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $type = TicketType::factory()->create(['has_seat' => true]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $type->id]);

        // Register route so redirectToRoute can build URL in test
        $this->app['router']->get('/seating/{code}/{id?}', fn() => 'ok')->name('seatingplans.show');

        $request = Request::create('/unseat', 'POST');
        $request->setUserResolver(fn() => $user);
        $request->setLaravelSession(app('session.store'));

        $controller = new SeatingPlanController();
        $resp = $controller->unseat($request, $event, $ticket);

        $this->assertEquals(302, $resp->getStatusCode());
        // no seat existed, but redirect fragment should still be present
        $this->assertStringContainsString('/seating/' . $event->code, $resp->getTargetUrl());
    }

    public function testSelectAbortsWhenSeatPlanMismatch()
    {
        $this->expectException(HttpException::class);
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay()]);
        $otherEvent = Event::factory()->create(['ends_at' => now()->addDay()]);
        $plan = SeatingPlan::factory()->create(['event_id' => $otherEvent->id]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id]);
        $type = TicketType::factory()->create(['has_seat' => true]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $type->id]);

        $request = Request::create('/select', 'POST');
        $request->setUserResolver(fn() => $user);

        $controller = new SeatingPlanController();
        $controller->select($request, $event, $ticket, $seat);
    }

    public function testSelectAbortsWhenTicketEventMismatch()
    {
        $this->expectException(HttpException::class);
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay()]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id]);
        $type = TicketType::factory()->create(['has_seat' => true]);
        $ticket = Ticket::factory()->create(['event_id' => Event::factory()->create()->id, 'user_id' => $user->id, 'ticket_type_id' => $type->id]);

        $request = Request::create('/select', 'POST');
        $request->setUserResolver(fn() => $user);

        $controller = new SeatingPlanController();
        $controller->select($request, $event, $ticket, $seat);
    }

    public function testSelectRedirectsWhenSeatNotPickable()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => true]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id]);
        $type = TicketType::factory()->create(['has_seat' => true]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $type->id]);

        // Register route used by controller for redirectToRoute
        $this->app['router']->get('/seating/{code}/{id?}', fn() => 'ok')->name('seatingplans.show');

        $request = Request::create('/select', 'POST');
        $request->setUserResolver(fn() => $user);
        $request->setLaravelSession(app('session.store'));

        $controller = new SeatingPlanController();
        $response = $controller->select($request, $event, $ticket, $seat);

        $this->assertStringContainsString('/seating/' . $event->code, $response->getTargetUrl());
        $this->assertNotEmpty($response->getSession()->get('errorMessage'));
    }

    public function testSelectMovesFromOldSeatDifferentPlan()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $plan1 = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $plan2 = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $oldSeat = Seat::factory()->create(['seating_plan_id' => $plan1->id]);
        $newSeat = Seat::factory()->create(['seating_plan_id' => $plan2->id]);
        $type = TicketType::factory()->create(['has_seat' => true]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $type->id]);

        // Associate old seat
        $oldSeat->ticket()->associate($ticket);
        $oldSeat->save();
        // ensure ticket relation is present so controller can see the old seat
        $ticket->setRelation('seat', $oldSeat);

        $request = Request::create('/select', 'POST');
        $request->setUserResolver(fn() => $user);

        // capture which Seat IDs had saved events fired
        $savedSeatIds = [];
        Seat::saved(function ($seat) use (&$savedSeatIds) {
            $savedSeatIds[] = $seat->id;
        });
        Seat::updated(function ($seat) use (&$savedSeatIds) {
            $savedSeatIds[] = $seat->id;
        });

        $controller = new SeatingPlanController();
        $controller->select($request, $event, $ticket, $newSeat);

        $this->assertNull($oldSeat->fresh()->ticket_id);
        $this->assertEquals($ticket->id, $newSeat->fresh()->ticket_id);

        // The new seat should always fire a saved event
        $this->assertContains($newSeat->id, $savedSeatIds, 'Expected new seat to trigger saved event');
        // When moving between different plans the controller should use save(), so oldSeat should have fired saved event
        $this->assertContains($oldSeat->id, $savedSeatIds, 'Expected old seat to trigger saved event when moved between plans');
    }

    public function testSelectMovesFromOldSeatSamePlan()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $oldSeat = Seat::factory()->create(['seating_plan_id' => $plan->id]);
        $newSeat = Seat::factory()->create(['seating_plan_id' => $plan->id]);
        $type = TicketType::factory()->create(['has_seat' => true]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $type->id]);

        $oldSeat->ticket()->associate($ticket);
        $oldSeat->save();
        // ensure ticket relation is present so controller can see the old seat
        $ticket->setRelation('seat', $oldSeat);

        $request = Request::create('/select', 'POST');
        $request->setUserResolver(fn() => $user);

        // capture which Seat IDs had saved events fired
        $savedSeatIds = [];
        Seat::saved(function ($seat) use (&$savedSeatIds) {
            $savedSeatIds[] = $seat->id;
        });
        Seat::updated(function ($seat) use (&$savedSeatIds) {
            $savedSeatIds[] = $seat->id;
        });

        $controller = new SeatingPlanController();
        $controller->select($request, $event, $ticket, $newSeat);

        $this->assertNull($oldSeat->fresh()->ticket_id);
        $this->assertEquals($ticket->id, $newSeat->fresh()->ticket_id);

        // The new seat should have fired a saved event
        $this->assertContains($newSeat->id, $savedSeatIds, 'Expected new seat to trigger saved event');
        // When moving within the same plan the controller should use saveQuietly() for the old seat, so no saved event for oldSeat
        $this->assertNotContains($oldSeat->id, $savedSeatIds, 'Expected old seat save to be quiet when moved within the same plan');
    }

    public function testShowRedirectsWhenTicketNotPickableOrNotManaged()
    {
        $viewer = User::factory()->create();
        $owner = User::factory()->create();

        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $type = TicketType::factory()->create(['has_seat' => true]);

        // Ticket owned by someone else and viewer cannot manage it
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $owner->id, 'ticket_type_id' => $type->id]);

        // Register route so redirectToRoute can build URL in test
        $this->app['router']->get('/seating/{code}/{id?}', fn() => 'ok')->name('seatingplans.show');

        $request = Request::create('/seating', 'GET');
        $request->setUserResolver(fn() => $viewer);
        $request->setLaravelSession(app('session.store'));

        $controller = new SeatingPlanController();
        $resp = $controller->show($request, $event, $ticket);

        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertNotEmpty($resp->getSession()->get('errorMessage'));
    }

    public function testShowCollectsAllowedSeatGroupsForCurrentTicket()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);

        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        $type = TicketType::factory()->create(['has_seat' => true]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $type->id]);

        // Create a seat group and assignment that allows this user
        $group = new SeatGroup();
        $group->event()->associate($event);
        $group->name = 'Group A';
        $group->class = 'default';
        $group->save();

        $assignment = new SeatGroupAssignment();
        $assignment->group()->associate($group);
        $assignment->assignment_type = 'user';
        $assignment->assignment_type_id = $user->id;
        $assignment->save();

        $request = Request::create('/seating', 'GET');
        $request->setUserResolver(fn() => $user);
        $request->setLaravelSession(app('session.store'));

        $controller = new SeatingPlanController();
        $view = $controller->show($request, $event, $ticket);

        $data = $view->getData();
        $this->assertArrayHasKey('seatGroups', $data);
        $this->assertContains($group->id, $data['seatGroups']);
    }

    public function testShowSetsInfoMessageWhenSeatingLocked()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => true]);

        $request = Request::create('/seating', 'GET');
        $request->setUserResolver(fn() => $user);
        $request->setLaravelSession(app('session.store'));

        $controller = new SeatingPlanController();
        $view = $controller->show($request, $event);

        $this->assertTrue(is_object($view));
        $this->assertEquals('Seating is locked', $request->session()->get('infoMessage'));
    }

    public function testShowAjaxWithNonexistentPlanReturnsDefaultView()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id, 'code' => 'P1']);

        $request = Request::create('/seating', 'GET', ['plan' => 'NOPE']);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setUserResolver(fn() => $user);
        $request->setLaravelSession(app('session.store'));

        $controller = new SeatingPlanController();
        $view = $controller->show($request, $event);

        $data = $view->getData();
        $this->assertArrayNotHasKey('plan', $data);
    }

    public function testSelectReassignsWhenTicketHasExistingSeat()
    {
        // When ticket already has a seat, old seat should be disassociated and new seat assigned
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        $oldSeat = Seat::factory()->create(['seating_plan_id' => $plan->id, 'disabled' => 0]);
        $newSeat = Seat::factory()->create(['seating_plan_id' => $plan->id, 'disabled' => 0]);

        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);
        $type = TicketType::factory()->create(['event_id' => $event->id, 'has_seat' => 1]);
        $ticket->type()->associate($type);
        $ticket->save();

        // Associate old seat with ticket
        $oldSeat->ticket()->associate($ticket);
        $oldSeat->save();

        $controller = new SeatingPlanController();
        $req = Request::create('/select', 'POST');
        $req->setUserResolver(function () use ($user) {
            return $user;
        });

        $resp = $controller->select($req, $event, $ticket, $newSeat);

        // Ensure old seat no longer has ticket and new seat is associated
        $this->assertNull($oldSeat->fresh()->ticket_id);
        $this->assertEquals($ticket->id, $newSeat->fresh()->ticket_id);
        $this->assertInstanceOf(RedirectResponse::class, $resp);
        $this->assertStringContainsString($event->code, $resp->getTargetUrl());
    }

    public function testSelectAssignsWhenTicketHasNoSeat()
    {
        // When ticket has no seat, selecting should associate the seat with the ticket
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id, 'disabled' => 0]);
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);
        $type = TicketType::factory()->create(['event_id' => $event->id, 'has_seat' => 1]);
        $ticket->type()->associate($type);
        $ticket->save();

        $controller = new SeatingPlanController();
        $req = Request::create('/select', 'POST');
        $req->setUserResolver(function () use ($user) {
            return $user;
        });

        $resp = $controller->select($req, $event, $ticket, $seat);

        $this->assertEquals($ticket->id, $seat->fresh()->ticket_id);
        $this->assertInstanceOf(RedirectResponse::class, $resp);
        $this->assertStringContainsString($event->code, $resp->getTargetUrl());
    }

    // Single-purpose: when !$seat->canPick($ticket->user) is true
    public function testSelectShortCircuitsWhenSeatNotPickable()
    {
        $user = User::factory()->create();
        // Event in future but seat disabled -> canPick should be false
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id, 'disabled' => 1]);
        $type = TicketType::factory()->create(['has_seat' => true]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $type->id]);

        // Register route used by controller for redirectToRoute
        $this->app['router']->get('/seating/{code}/{id?}', fn() => 'ok')->name('seatingplans.show');

        $request = Request::create('/select', 'POST');
        $request->setUserResolver(fn() => $user);
        $request->setLaravelSession(app('session.store'));

        $controller = new SeatingPlanController();
        $resp = $controller->select($request, $event, $ticket, $seat);

        $this->assertEquals(302, $resp->getStatusCode());
        $this->assertNotEmpty($resp->getSession()->get('errorMessage'));
    }

    // Single-purpose: when $ticket->seat is true (old seat should be disassociated)
    public function testSelectReassignsOldSeatWhenTicketHasSeat()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        $oldSeat = Seat::factory()->create(['seating_plan_id' => $plan->id, 'disabled' => 0]);
        $newSeat = Seat::factory()->create(['seating_plan_id' => $plan->id, 'disabled' => 0]);

        $type = TicketType::factory()->create(['has_seat' => true]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $type->id]);

        // associate old seat
        $oldSeat->ticket()->associate($ticket);
        $oldSeat->save();

        $request = Request::create('/select', 'POST');
        $request->setUserResolver(fn() => $user);

        $controller = new SeatingPlanController();
        $controller->select($request, $event, $ticket, $newSeat);

        $this->assertNull($oldSeat->fresh()->ticket_id);
        $this->assertEquals($ticket->id, $newSeat->fresh()->ticket_id);
    }

    // Single-purpose: when $ticket->seat is false (assign new seat)
    public function testSelectAssignsSeatWhenTicketHasNoSeat()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id, 'disabled' => 0]);
        $type = TicketType::factory()->create(['has_seat' => true]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $type->id]);

        $request = Request::create('/select', 'POST');
        $request->setUserResolver(fn() => $user);

        $controller = new SeatingPlanController();
        $controller->select($request, $event, $ticket, $seat);

        $this->assertEquals($ticket->id, $seat->fresh()->ticket_id);
    }
}
