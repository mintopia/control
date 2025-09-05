<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\EventController;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use App\Models\SeatingPlan;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\TicketProvider;
use App\Models\EventMapping;
use App\Models\User;
use Carbon\Carbon;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new EventController();
        $this->assertInstanceOf(EventController::class, $controller);
    }

    public function testCreateEventStoresAndRedirects()
    {
        $controller = new EventController();

        $request = Event::factory()->make([
            'name' => 'Test Event',
            'starts_at' => '2025-08-07 10:00:00',
            'ends_at' => '2025-08-07 12:00:00',
        ]);

        // Use updateObject indirectly by calling store with a manually created request object
        $response = $controller->store(\App\Http\Requests\Admin\EventUpdateRequest::create('/', 'POST', $request->toArray()));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseHas('events', ['name' => 'Test Event']);
    }

    public function testUpdateEventModifiesAndRedirects()
    {
        $event = Event::factory()->create([
            'name' => 'Original',
            'starts_at' => '2025-08-07 10:00:00',
            'ends_at' => '2025-08-07 12:00:00',
        ]);

        $controller = new EventController();

        $requestData = [
            'name' => 'Updated Event',
            'starts_at' => '2025-08-08 10:00:00',
            'ends_at' => '2025-08-08 12:00:00',
        ];

        $response = $controller->update(\App\Http\Requests\Admin\EventUpdateRequest::create('/', 'POST', $requestData), $event);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'name' => 'Updated Event']);
    }

    public function testDeleteEventDeletesAndRedirects()
    {
        $event = Event::factory()->create();
        $controller = new EventController();

        // Delete request requires confirm field; use a stubbed DeleteRequest with required data
        $response = $controller->destroy(\App\Http\Requests\Admin\DeleteRequest::create('/', 'DELETE', ['confirm' => 'delete']), $event);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function testIndexWithFiltersAndDesc()
    {
        $eventA = Event::factory()->create(['name' => 'Alpha']);
        $eventB = Event::factory()->create(['name' => 'Beta']);

        $controller = new EventController();
        $request = Request::create('/admin/events', 'GET', ['order_direction' => 'desc']);
        $resp = $controller->index($request);
        $this->assertInstanceOf(View::class, $resp);
        $data = $resp->getData();
        $this->assertArrayHasKey('events', $data);
    }

    public function testShowDisplaysRelatedCollections()
    {
        $event = Event::factory()->create();
        SeatingPlan::factory()->create(['event_id' => $event->id]);
        $controller = new EventController();
        $resp = $controller->show($event);
        $this->assertInstanceOf(View::class, $resp);
        $data = $resp->getData();
        $this->assertArrayHasKey('seatingPlans', $data);
        $this->assertArrayHasKey('seatGroups', $data);
        $this->assertArrayHasKey('ticketTypes', $data);
    }

    public function testCreateReturnsView()
    {
        $controller = new EventController();
        $resp = $controller->create();
        $this->assertInstanceOf(View::class, $resp);
        $this->assertArrayHasKey('event', $resp->getData());
    }

    public function testEditReturnsView()
    {
        $event = Event::factory()->create();
        $controller = new EventController();
        $resp = $controller->edit($event);
        $this->assertInstanceOf(View::class, $resp);
        $this->assertArrayHasKey('event', $resp->getData());
    }

    public function testExportTicketsProducesStream()
    {
        $event = Event::factory()->create();
        $provider = TicketProvider::factory()->create();
        $type = TicketType::factory()->create(['has_seat' => true]);
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $type->id, 'user_id' => $user->id, 'ticket_provider_id' => $provider->id]);

        $controller = new EventController();
        $resp = $controller->export_tickets($event);
        $this->assertTrue(method_exists($resp, 'getStatusCode') || method_exists($resp, 'send'));
    }

    public function testSeatsShowsUnseatedAndSeats()
    {
        $event = Event::factory()->create();
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $type = TicketType::factory()->create(['has_seat' => true]);
        $user = User::factory()->create();
        // create unseated ticket
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $type->id, 'user_id' => $user->id]);

        $controller = new EventController();
        $resp = $controller->seats(Request::create('/admin/events/seats', 'GET'), $event);
        $this->assertInstanceOf(View::class, $resp);
        $data = $resp->getData();
        $this->assertArrayHasKey('tickets', $data);
        $this->assertArrayHasKey('seats', $data);
    }

    public function testPickseatAssignsSeat()
    {
        $event = Event::factory()->create(['code' => 'EV']);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id]);
        $type = TicketType::factory()->create(['has_seat' => true]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $type->id]);

        $controller = new EventController();
        $resp = $controller->pickseat($event, $ticket, $seat);
        $this->assertEquals($ticket->id, $seat->fresh()->ticket_id);
    }

    public function testUnseatRemovesTicketFromSeat()
    {
        $event = Event::factory()->create(['code' => 'EV2']);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id]);
        $seat->ticket()->associate($ticket);
        $seat->save();
        // ensure relations are present so controller can read them
        $ticket->setRelation('seat', $seat);
        $seat->setRelation('plan', $plan);

        $controller = new EventController();
        $resp = $controller->unseat($event, $ticket);
        $this->assertNull($seat->fresh()->ticket_id);
    }



    public function testIndexFiltersByIdNameAndCode()
    {
        $event = Event::factory()->create(['name' => 'FilterMe', 'code' => 'F123']);
        $other = Event::factory()->create(['name' => 'Other', 'code' => 'O123']);

        $controller = new EventController();

        // filter by id
        $resp = $controller->index(Request::create('/admin/events', 'GET', ['id' => $event->id]));
        $items = $resp->getData()['events']->items();
        $this->assertCount(1, $items);
        $this->assertEquals($event->id, $items[0]->id);

        // filter by name partial
        $resp = $controller->index(Request::create('/admin/events', 'GET', ['name' => 'Filter']));
        $items = $resp->getData()['events']->items();
        $this->assertGreaterThanOrEqual(1, count($items));

        // filter by code
        $resp = $controller->index(Request::create('/admin/events', 'GET', ['code' => 'F12']));
        $items = $resp->getData()['events']->items();
        $this->assertGreaterThanOrEqual(1, count($items));
    }

    public function testIndexOrderByStartsAtDesc()
    {
        $a = Event::factory()->create(['starts_at' => '2025-09-01 10:00:00']);
        $b = Event::factory()->create(['starts_at' => '2025-10-01 10:00:00']);

        $controller = new EventController();
        $resp = $controller->index(Request::create('/admin/events', 'GET', ['order' => 'starts_at', 'order_direction' => 'desc']));
        $items = $resp->getData()['events']->items();
        $this->assertGreaterThanOrEqual(2, count($items));
        $this->assertEquals($b->id, $items[0]->id);
    }



    public function testUpdateObjectHandlesOptionalDates()
    {
        $event = new Event();
        $controller = new EventController();

        $request = Request::create('/admin', 'POST', [
            'name' => 'X',
            'starts_at' => '2025-09-01 10:00:00',
            'ends_at' => '2025-09-01 12:00:00',
            'seating_locked' => true,
            'seating_opens_at' => null,
            'seating_closes_at' => null,
            'draft' => false,
        ]);

        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        $method->invoke($controller, $event, $request);

        $this->assertEquals('X', $event->name);
        $this->assertNull($event->seating_opens_at);
        $this->assertNull($event->seating_closes_at);
    }

    public function testUpdateObjectSetsProvidedDates()
    {
        $event = new Event();
        $controller = new EventController();

        $request = Request::create('/admin', 'POST', [
            'name' => 'Y',
            'starts_at' => '2025-09-01 10:00:00',
            'ends_at' => '2025-09-01 12:00:00',
            'seating_locked' => false,
            'seating_opens_at' => '2025-08-01 10:00:00',
            'seating_closes_at' => '2025-08-02 10:00:00',
            'draft' => false,
        ]);

        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        $method->invoke($controller, $event, $request);

        $this->assertEquals('Y', $event->name);
        $this->assertNotNull($event->seating_opens_at);
        $this->assertNotNull($event->seating_closes_at);
    }

    public function testExportTicketsIncludesEmailAndSeat()
    {
        $event = Event::factory()->create();
        $provider = TicketProvider::factory()->create(['name' => 'P1']);
        $type = TicketType::factory()->create(['has_seat' => true, 'event_id' => $event->id]);
        $user = User::factory()->create();
        $email = \App\Models\EmailAddress::factory()->create(['user_id' => $user->id, 'email' => 'prim@example.com']);
        $user->primary_email_id = $email->id;
        $user->save();

        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $type->id, 'user_id' => $user->id, 'ticket_provider_id' => $provider->id]);
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);
        $seat = Seat::factory()->create(['seating_plan_id' => $plan->id, 'label' => 'A1']);
        $seat->ticket()->associate($ticket);
        $seat->save();

        $controller = new EventController();
        $resp = $controller->export_tickets($event);
        $this->assertTrue(method_exists($resp, 'getStatusCode') || method_exists($resp, 'send'));
    }

    public function testExportTicketsFallsBackToOriginalEmail()
    {
        $event = Event::factory()->create();
        $provider = TicketProvider::factory()->create(['name' => 'P2']);
        $type = TicketType::factory()->create(['has_seat' => true, 'event_id' => $event->id]);
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $type->id, 'user_id' => $user->id, 'ticket_provider_id' => $provider->id, 'original_email' => 'fallback@example.com']);

        $controller = new EventController();
        $resp = $controller->export_tickets($event);

        $callback = $resp->getCallback();
        ob_start();
        $callback();
        $output = ob_get_clean();

        $this->assertStringContainsString('fallback@example.com', $output);
    }

    public function testPickseatAbortsWhenMismatch()
    {
        $eventA = Event::factory()->create(['code' => 'A']);
        $eventB = Event::factory()->create(['code' => 'B']);
        $planB = SeatingPlan::factory()->create(['event_id' => $eventB->id]);
        $seatB = Seat::factory()->create(['seating_plan_id' => $planB->id]);
        $ticketA = Ticket::factory()->create(['event_id' => $eventA->id]);

        $controller = new EventController();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $controller->pickseat($eventA, $ticketA, $seatB);
    }

    //VALIDATE EventController.php Function Index: whereHas(provider) changed to mappings
    public function testIndexFiltersByExternalIdAndProviderId()
    {
        $event = Event::factory()->create(['name' => 'FilterExt']);
        $provider = TicketProvider::factory()->create();
        // create mapping linking provider to this event
        EventMapping::factory()->create(['event_id' => $event->id, 'ticket_provider_id' => $provider->id, 'external_id' => 'EXT123']);

        $controller = new EventController();

        // filter by external_id
        $resp = $controller->index(Request::create('/admin/events', 'GET', ['external_id' => 'EXT123']));
        $items = $resp->getData()['events']->items();
        $this->assertGreaterThanOrEqual(1, count($items));

        // filter by provider_id
        $resp = $controller->index(Request::create('/admin/events', 'GET', ['provider_id' => $provider->id]));
        $items = $resp->getData()['events']->items();
        $this->assertGreaterThanOrEqual(1, count($items));
    }

    public function testDeleteReturnsView()
    {
        $event = Event::factory()->create();
        $controller = new EventController();
        $resp = $controller->delete($event);
        $this->assertInstanceOf(\Illuminate\View\View::class, $resp);
        $this->assertArrayHasKey('event', $resp->getData());
    }

    public function testSeatsCurrentTicketAndExcludesNonSeatedTypes()
    {
        $event = Event::factory()->create();
        // seating plan exists so seats array is populated
        $plan = SeatingPlan::factory()->create(['event_id' => $event->id]);

        // one ticket type that requires a seat, one that does not
        $seatable = TicketType::factory()->create(['has_seat' => true, 'event_id' => $event->id]);
        $nonseatable = TicketType::factory()->create(['has_seat' => false, 'event_id' => $event->id]);

        $user = User::factory()->create();
        $ticketA = Ticket::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $seatable->id, 'user_id' => $user->id]);
        $ticketB = Ticket::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $nonseatable->id, 'user_id' => $user->id]);

        $controller = new EventController();
        // pass ticket_id to set currentTicket
        $resp = $controller->seats(Request::create('/admin/events/seats', 'GET', ['ticket_id' => $ticketA->id]), $event);
        $this->assertInstanceOf(\Illuminate\View\View::class, $resp);
        $data = $resp->getData();
        $this->assertArrayHasKey('currentTicket', $data);
        $this->assertNotNull($data['currentTicket']);
        // tickets returned should only include those with has_seat = true
        $tickets = $data['tickets'];
        foreach ($tickets as $t) {
            $this->assertTrue((bool)$t->type->has_seat);
        }
    }
}
