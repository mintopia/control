<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\SeatingPlanController;
use App\Models\Event;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\SeatingPlan;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

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
        $request = new \Illuminate\Http\Request();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        $request->setLaravelSession(app('session.store'));
        $controller = new SeatingPlanController();
        $response = $controller->index($request);
        $this->assertTrue(is_object($response));
    }

    public function testShowReturnsView()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['ends_at' => now()->addDay(), 'seating_locked' => false]);
        $request = new \Illuminate\Http\Request();
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
        \App\Models\ClanRole::factory()->create(['code' => 'leader']);
        $clan = \App\Models\Clan::factory()->create();
        \Database\Factories\ClanMembershipFactory::new()->create(['clan_id' => $clan->id, 'user_id' => $user->id, 'clan_role_id' => 1]);
        \Database\Factories\ClanMembershipFactory::new()->create(['clan_id' => $clan->id, 'user_id' => $other->id, 'clan_role_id' => 1]);

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

    public function testSelectAbortsWhenSeatPlanMismatch()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
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
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
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

        $request = Request::create('/select', 'POST');
        $request->setUserResolver(fn() => $user);

        $controller = new SeatingPlanController();
        $controller->select($request, $event, $ticket, $newSeat);

        $this->assertNull($oldSeat->fresh()->ticket_id);
        $this->assertEquals($ticket->id, $newSeat->fresh()->ticket_id);
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

        $request = Request::create('/select', 'POST');
        $request->setUserResolver(fn() => $user);

        $controller = new SeatingPlanController();
        $controller->select($request, $event, $ticket, $newSeat);

        $this->assertNull($oldSeat->fresh()->ticket_id);
        $this->assertEquals($ticket->id, $newSeat->fresh()->ticket_id);
    }
}
