<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\TicketController;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\TicketProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    //FIX Illuminate\Database\QueryException: SQLSTATE[HY000]: General error: 1 ambiguous column name: id (Connection: sqlite, SQL: select count(*) as aggregate from "tickets" left join "seats" on "seats"."ticket_id" = "tickets"."id" where "id" = 1 and exists (select * from "events" where "tickets"."event_id" = "events"."id" and "code" = EVT-5290))
    // This may need adaptation to a migration file?
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
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
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
        $req = \App\Http\Requests\Admin\TicketUpdateRequest::create('/', 'POST', ['ticket_type_id' => $type->id, 'reference' => 'R1']);
        try {
            $controller->store($req);
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $ex) {
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
        $ref = new \ReflectionClass($controller);
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
        $req = \App\Http\Requests\Admin\TicketUpdateRequest::create('/', 'POST', ['reference' => 'UPDATED', 'ticket_type_id' => $type->id]);
        try {
            $controller->update($req, $ticket);
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $ex) {
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
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $ex) {
            // ignore
        }
        $this->assertNull(Ticket::find($ticket->id));
    }

    public function testImportShowAndProcess()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $user = \App\Models\User::factory()->create();
        // ensure internal provider exists
        \App\Models\TicketProvider::factory()->create(['code' => 'internal']);

        // create CSV rows: typeId,userId,seatLabel
        $csv = $type->id . ',' . $user->id . ",\n";
        $tmp = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tmp, $csv);
        $uploaded = new UploadedFile($tmp, 'tickets.csv', 'text/csv', null, true);

        $req = \App\Http\Requests\Admin\TicketImportRequest::create('/', 'POST', [], [], ['csv' => $uploaded]);
        $req->setLaravelSession($this->app['session.store']);

        $controller = new TicketController();
        $resp = $controller->import_show($req);
        $this->assertTrue(is_object($resp));

        // session should now contain imports - get them and process
        $this->app['session.store']->put('imports', $this->app['session.store']->get('imports'));
        $req2 = request();
        $req2->setLaravelSession($this->app['session.store']);
        try {
            $controller->import_process($req2);
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $ex) {
            // ignore
        }

        $this->assertDatabaseCount('tickets', 1);
    }
}
