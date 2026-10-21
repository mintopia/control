<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\TicketController;
use App\Http\Requests\Admin\TicketImportRequest;
use App\Http\Requests\Admin\TicketUpdateRequest;
use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatingPlan;
use App\Models\Ticket;
use App\Models\TicketProvider;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexAndViews()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        $ticket = Ticket::factory()->for($event)->for($type, 'type')->for($provider, 'provider')->create();

        $c = new TicketController();
        // exercise various index branches: id filter, event join, ordering by seat (will still return view)
        $req = request()->merge(['event' => $event->code, 'order' => 'seat', 'order_direction' => 'desc']);
        $this->assertTrue(is_object($c->index($req)));
        $this->assertTrue(is_object($c->show($ticket)));
        $this->assertTrue(is_object($c->edit($ticket)));
        $this->assertTrue(is_object($c->delete($ticket)));
    }

    public function testCreateRequiresEventAndReturnsView()
    {
        $c = new TicketController();
        // missing event should abort 404
        $this->expectException(HttpException::class);
        $c->create(request());

        // with event parameter should return a view-like object
        $event = Event::factory()->create(['code' => 'EV1']);
        $req = request()->merge(['event' => $event->code]);
        $this->assertTrue(is_object($c->create($req)));
    }

    public function testStoreCreatesTicket()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        // ensure internal provider exists
        $internal = TicketProvider::factory()->create(['code' => 'internal']);

        $controller = new TicketController();
        $req = TicketUpdateRequest::create('/', 'POST', ['ticket_type_id' => $type->id, 'reference' => 'R1']);
        try {
            $controller->store($req);
        } catch (UrlGenerationException $ex) {
            // ignore redirects
        }

        $this->assertDatabaseHas('tickets', ['ticket_type_id' => $type->id]);
    }

    public function testUpdateObjectSetsFields()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        $ticket = Ticket::factory()->for($event)->for($type, 'type')->for($provider, 'provider')->create();

        $controller = new TicketController();
        $req = request()->create('/', 'POST', ['reference' => 'NEWREF', 'user_id' => null, 'ticket_type_id' => $type->id]);
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        $method->invoke($controller, $ticket, $req);

        $this->assertEquals('NEWREF', $ticket->fresh()->reference);
    }

    public function testUpdatePersistsChanges()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        $ticket = Ticket::factory()->for($event)->for($type, 'type')->for($provider, 'provider')->create(['reference' => 'OLD']);

        $controller = new TicketController();
        $req = TicketUpdateRequest::create('/', 'POST', ['reference' => 'UPDATED', 'ticket_type_id' => $type->id]);
        try {
            $controller->update($req, $ticket);
        } catch (UrlGenerationException $ex) {
            // ignore
        }
        $this->assertEquals('UPDATED', $ticket->fresh()->reference);
    }

    public function testDestroyDeletesTicket()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        $ticket = Ticket::factory()->for($event)->for($type, 'type')->for($provider, 'provider')->create();

        $controller = new TicketController();
        try {
            $controller->destroy($ticket);
        } catch (UrlGenerationException $ex) {
            // ignore
        }
        $this->assertNull(Ticket::find($ticket->id));
    }

    public function testImportShowAndProcess()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $user = User::factory()->create();
        // ensure internal provider exists
        TicketProvider::factory()->create(['code' => 'internal']);

        // create CSV rows: typeId,userId,seatLabel
        $csv = $type->id . ',' . $user->id . ",\n";
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tmp, $csv);
        $uploaded = new UploadedFile($tmp, 'tickets.csv', 'text/csv', null, true);

        $req = TicketImportRequest::create('/', 'POST', [], [], ['csv' => $uploaded]);
        $req->setLaravelSession($this->app['session.store']);

        $controller = new TicketController();
        $resp = $controller->importShow($req);
        $this->assertTrue(is_object($resp));

        // session should now contain imports - get them and process
        $this->app['session.store']->put('imports', $this->app['session.store']->get('imports'));
        $req2 = request();
        $req2->setLaravelSession($this->app['session.store']);
        try {
            $controller->importProcess($req2);
        } catch (UrlGenerationException $ex) {
            // ignore
        }

        $this->assertDatabaseCount('tickets', 1);
    }

    public function testIndexFiltersById()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        $ticket = Ticket::factory()->for($event)->for($type, 'type')->for($provider, 'provider')->create();

        $controller = new TicketController();
        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['id' => $ticket->id]));
        $items = $resp->getData()['tickets']->items();
        $this->assertCount(1, $items);
        $this->assertEquals($ticket->id, $items[0]->id);
    }

    public function testIndexFiltersByUserEventTypeExternalProviderSeatOriginalEmail()
    {
        $event = Event::factory()->create(['code' => 'EVX']);
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        $user = User::factory()->create(['nickname' => 'Zed']);

        $ticket = Ticket::factory()->for($event)->for($type, 'type')->for($provider, 'provider')->create(['user_id' => $user->id, 'external_id' => 'EXT-1', 'original_email' => 'orig@example.com']);

        // seat
        $seat = Seat::factory()->create(['ticket_id' => $ticket->id, 'label' => 'S1']);

        $controller = new TicketController();

        // user_id
        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['user_id' => $user->id]));
        $this->assertGreaterThanOrEqual(1, count($resp->getData()['tickets']->items()));

        // event by code
        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['event' => $event->code]));
        $this->assertGreaterThanOrEqual(1, count($resp->getData()['tickets']->items()));

        // ticket_type_id
        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['ticket_type_id' => $type->id]));
        $this->assertGreaterThanOrEqual(1, count($resp->getData()['tickets']->items()));

        // external_id
        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['external_id' => 'EXT-1']));
        $this->assertGreaterThanOrEqual(1, count($resp->getData()['tickets']->items()));

        // provider_id
        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['provider_id' => $provider->id]));
        $this->assertGreaterThanOrEqual(1, count($resp->getData()['tickets']->items()));

        // seat
        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['seat' => 'S1']));
        $this->assertGreaterThanOrEqual(1, count($resp->getData()['tickets']->items()));

        // original_email
        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['original_email' => 'orig@example.com']));
        $this->assertGreaterThanOrEqual(1, count($resp->getData()['tickets']->items()));
    }

    public function testIndexOrderCreatedAtAndExternalReference()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        $a = Ticket::factory()->for($event)->for($type, 'type')->for($provider, 'provider')->create(['created_at' => now()->subDay(), 'external_id' => 'A', 'reference' => 'R1']);
        $b = Ticket::factory()->for($event)->for($type, 'type')->for($provider, 'provider')->create(['created_at' => now(), 'external_id' => 'B', 'reference' => 'R2']);

        $controller = new TicketController();
        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['order' => 'created_at', 'order_direction' => 'desc']));
        $items = $resp->getData()['tickets']->items();
        $this->assertEquals($b->id, $items[0]->id);

        // external_id order asc
        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['order' => 'external_id', 'order_direction' => 'asc']));
        $items = $resp->getData()['tickets']->items();
        $this->assertEquals($a->id, $items[0]->id);
    }

    public function testIndexOrderByEvent()
    {
        $controller = new TicketController();

        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['order' => 'event', 'order_direction' => 'asc']));
        $items = $resp->getData()['tickets']->items();
        $eventNames = array_map(fn($i) => $i->event->name, $items);
        $sortedEventNames = $eventNames;
        sort($sortedEventNames, SORT_STRING);
        $this->assertEquals($sortedEventNames, $eventNames);
    }

    public function testIndexOrderByType()
    {
        $controller = new TicketController();

        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['order' => 'type', 'order_direction' => 'asc']));
        $items = $resp->getData()['tickets']->items();
        $typeNames = array_map(fn($i) => $i->type->name, $items);
        $sortedTypeNames = $typeNames;
        sort($sortedTypeNames, SORT_STRING);
        $this->assertEquals($sortedTypeNames, $typeNames);
    }

    public function testIndexOrderByUser()
    {
        $e1 = Event::factory()->create(['name' => 'Alpha']);
        $e2 = Event::factory()->create(['name' => 'Beta']);
        $type = TicketType::factory()->for($e1)->create(['name' => 'TypeA']);
        $provider = TicketProvider::factory()->create();
        $controller = new TicketController();
        $typeA = TicketType::factory()->for($e1)->create(['name' => 'AAA']);
        $typeB = TicketType::factory()->for($e1)->create(['name' => 'ZZZ']);

        // order by user
        $uA = User::factory()->create(['nickname' => 'AA']);
        $uB = User::factory()->create(['nickname' => 'ZZ']);
        $tua = Ticket::factory()->for($e1)->for($typeA, 'type')->for($provider, 'provider')->create(['user_id' => $uA->id]);
        $tub = Ticket::factory()->for($e1)->for($typeA, 'type')->for($provider, 'provider')->create(['user_id' => $uB->id]);
        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['order' => 'user', 'order_direction' => 'asc']));
        $items = $resp->getData()['tickets']->items();
        $nicknames = array_map(fn($i) => $i->user->nickname ?? '', $items);
        $sortedNicks = $nicknames;
        sort($sortedNicks, SORT_STRING);
        $this->assertEquals($sortedNicks, $nicknames);
    }

    public function testIndexOrderBySeat()
    {
        $e1 = Event::factory()->create(['name' => 'Alpha']);
        $provider = TicketProvider::factory()->create();
        $controller = new TicketController();
        $typeA = TicketType::factory()->for($e1)->create(['name' => 'AAA']);
        $typeB = TicketType::factory()->for($e1)->create(['name' => 'ZZZ']);
        $ta = Ticket::factory()->for($e1)->for($typeA, 'type')->for($provider, 'provider')->create();
        $tb = Ticket::factory()->for($e1)->for($typeB, 'type')->for($provider, 'provider')->create();

        // order by seat
        $plan = SeatingPlan::factory()->create(['event_id' => $e1->id]);
        $seat1 = Seat::factory()->create(['seating_plan_id' => $plan->id, 'row' => 'A', 'number' => 1]);
        $seat2 = Seat::factory()->create(['seating_plan_id' => $plan->id, 'row' => 'B', 'number' => 1]);
        // assign seats to tickets
        $seat1->ticket()->associate($ta);
        $seat1->save();
        $seat2->ticket()->associate($tb);
        $seat2->save();

        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['order' => 'seat', 'order_direction' => 'asc']));
        $items = $resp->getData()['tickets']->items();
        $seatKeys = array_map(fn($i) => ($i->seat->row ?? '') . str_pad($i->seat->number ?? 0, 4, '0', STR_PAD_LEFT), $items);
        $sortedSeatKeys = $seatKeys;
        sort($sortedSeatKeys, SORT_STRING);
        $this->assertEquals($sortedSeatKeys, $seatKeys);
    }

    public function testIndexOrderByDefault()
    {
        $e1 = Event::factory()->create(['name' => 'Alpha']);
        $controller = new TicketController();

        $resp = $controller->index(Request::create('/admin/tickets', 'GET', []));
        $items = $resp->getData()['tickets']->items();
        $ids = array_map(fn($i) => $i->id, $items);
        $sorted = $ids;
        sort($sorted, SORT_NUMERIC);
        $this->assertEquals($sorted, $ids);
    }

    public function testCreateAssociatesEvent()
    {
        $controller = new TicketController();
        $event = Event::factory()->create(['code' => 'EVX2']);
        $resp = $controller->create(Request::create('/admin/tickets/create', 'GET', ['event' => $event->code]));
        $this->assertTrue(is_object($resp));
        $this->assertEquals($event->id, $resp->getData()['ticket']->event->id);
    }

    public function testImportReturnsView()
    {
        $controller = new TicketController();
        $resp = $controller->import();
        $this->assertTrue(is_object($resp));
    }

    public function testIndexFiltersByReferenceStringReturnsNoResults()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        // create a ticket with a string reference
        $ticket = Ticket::factory()->for($event)->for($type, 'type')->for($provider, 'provider')->create(['reference' => 'REF-ABC']);

        $controller = new TicketController();
        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['reference' => 'REF-ABC']));
        $items = $resp->getData()['tickets']->items();
        // Because the controller uses whereId(...) for the reference filter, a string reference should not match and return no items
        $this->assertCount(0, $items);
    }

    public function testIndexFiltersByReferenceAsIdReturnsTicket()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        $ticket = Ticket::factory()->for($event)->for($type, 'type')->for($provider, 'provider')->create();

        $controller = new TicketController();
        // passing the numeric id as 'reference' will match due to whereId(...) being used
        $resp = $controller->index(Request::create('/admin/tickets', 'GET', ['reference' => $ticket->id]));
        $items = $resp->getData()['tickets']->items();
        $this->assertCount(1, $items);
        $this->assertEquals($ticket->id, $items[0]->id);
    }
}
