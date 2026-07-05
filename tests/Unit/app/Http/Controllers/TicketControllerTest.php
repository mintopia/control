<?php

namespace Tests\Unit\app\Http\Controllers;

use App\Http\Controllers\TicketController;
use App\Http\Requests\TicketTransferRequest;
use App\Models\Event;
use App\Models\Seat;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Tests\TestCase;

/**
 * TicketController tests using factories and Eloquent.
 */
class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new TicketController();
        $this->assertInstanceOf(TicketController::class, $controller);
    }

    public function testIndexFiltersDraftEventsForNonAdmin()
    {
        $user = User::factory()->create();
        $ticketForDraft = Ticket::factory()->create(['event_id' => Event::factory()->create(['draft' => true, 'starts_at' => now(), 'ends_at' => now()->addHour()])->id, 'user_id' => $user->id]);
        $ticketForLive = Ticket::factory()->create(['event_id' => Event::factory()->create(['draft' => false, 'starts_at' => now(), 'ends_at' => now()->addHour()])->id, 'user_id' => $user->id]);

        $this->actingAs($user);
        $controller = new TicketController();
        $request = Request::create('/', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $controller->index($request);
        $this->assertInstanceOf(View::class, $response);
        $data = $response->getData();
        $tickets = $data['tickets'];
        $ids = $tickets->pluck('id')->all();
        $this->assertNotContains($ticketForDraft->id, $ids);
        $this->assertContains($ticketForLive->id, $ids);
    }

    public function testShowReturnsViewWithTicket()
    {
        $ticket = Ticket::factory()->create(['event_id' => Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()])->id]);
        $controller = new TicketController();
        $request = Request::create('/', 'GET');
        $response = $controller->show($request, $ticket);
        $this->assertInstanceOf(View::class, $response);
        $data = $response->getData();
        $this->assertArrayHasKey('ticket', $data);
        $this->assertEquals($ticket->id, $data['ticket']->id);
    }

    public function testUpdateGenerateAndRemoveTransferCode()
    {
        $ticket = Ticket::factory()->create(['event_id' => Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()])->id]);
        $controller = new TicketController();

        // generate
        $request = Request::create('/', 'POST', ['generate' => '1']);
        $response = $controller->update($request, $ticket);
        $this->assertStringContainsString('/tickets/' . $ticket->id, $response->getTargetUrl());

        // remove
        $ticket->transfer_code = 'abc123';
        $ticket->save();
        $request = Request::create('/', 'POST', ['remove' => '1']);
        $response = $controller->update($request, $ticket);
        $this->assertStringContainsString('/tickets/' . $ticket->id, $response->getTargetUrl());
        $this->assertNull($ticket->fresh()->transfer_code);
    }

    public function testTransferTransfersTicketToUser()
    {
        Setting::create(['code' => 'disable-ticket-transfers', 'name' => 'Disable Transfers', 'value' => 0]);
        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $ticket = Ticket::factory()->create(['event_id' => Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()])->id, 'user_id' => $owner->id, 'transfer_code' => 'tx-1']);

        $this->actingAs($recipient);
        $request = TicketTransferRequest::create('/', 'POST', ['code' => 'tx-1']);
        $request->setUserResolver(function () use ($recipient) {
            return $recipient;
        });

        $controller = new TicketController();
        $response = $controller->transfer($request);
        $this->assertStringContainsString('/tickets/' . $ticket->id, $response->getTargetUrl());
        $this->assertEquals($recipient->id, $ticket->fresh()->user_id);
    }

    public function testUpdateDefaultRedirectsToShow()
    {
        $ticket = Ticket::factory()->create(['event_id' => Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()])->id]);
        $controller = new TicketController();
        $request = Request::create('/', 'POST', []);
        $response = $controller->update($request, $ticket);
        $this->assertStringContainsString('/tickets/' . $ticket->id, $response->getTargetUrl());
    }

    public function testUpdateWhenTransfersDisabledReturnsError()
    {
        Setting::create(['code' => 'disable-ticket-transfers', 'name' => 'Disable Transfers', 'value' => 1]);
        $ticket = Ticket::factory()->create(['event_id' => Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()])->id]);
        $controller = new TicketController();
        $request = Request::create('/', 'POST', ['generate' => '1']);
        $response = $controller->update($request, $ticket);
        $this->assertStringContainsString('/tickets/' . $ticket->id, $response->getTargetUrl());
        $this->assertNotEmpty($response->getSession()->get('errorMessage'));
    }

    public function testTransferWhenTransfersDisabledReturnsError()
    {
        Setting::create(['code' => 'disable-ticket-transfers', 'name' => 'Disable Transfers', 'value' => 1]);
        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $ticket = Ticket::factory()->create(['event_id' => Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()])->id, 'user_id' => $owner->id, 'transfer_code' => 'tx-2']);

        $this->actingAs($recipient);
        $request = TicketTransferRequest::create('/', 'POST', ['code' => 'tx-2']);
        $request->setUserResolver(function () use ($recipient) {
            return $recipient;
        });

        $controller = new TicketController();
        $response = $controller->transfer($request);
        $this->assertStringContainsString('/tickets', $response->getTargetUrl());
        $this->assertNotEmpty($response->getSession()->get('errorMessage'));
    }

    public function testIndexDefaultsToNewestEventFirst()
    {
        $user = User::factory()->create();
        $oldEvent = Event::factory()->create(['draft' => false, 'starts_at' => now()->subDays(5), 'ends_at' => now()->subDays(5)->addHour()]);
        $newEvent = Event::factory()->create(['draft' => false, 'starts_at' => now()->addDays(5), 'ends_at' => now()->addDays(5)->addHour()]);
        $oldTicket = Ticket::factory()->create(['event_id' => $oldEvent->id, 'user_id' => $user->id]);
        $newTicket = Ticket::factory()->create(['event_id' => $newEvent->id, 'user_id' => $user->id]);

        $tickets = $this->indexResponse($user)->getData()['tickets'];

        $this->assertSame([$newTicket->id, $oldTicket->id], $tickets->pluck('id')->all());
    }

    public function testIndexPassesResolvedSortParamsToView()
    {
        $user = User::factory()->create();

        $data = $this->indexResponse($user)->getData();

        $this->assertSame(['order' => 'event', 'order_direction' => 'desc'], $data['params']);
    }

    public function testIndexSortsByReference()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['draft' => false, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        $a = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'reference' => 'AAA-001']);
        $z = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'reference' => 'ZZZ-999']);

        $asc = $this->indexResponse($user, ['order' => 'reference', 'order_direction' => 'asc'])->getData()['tickets'];
        $this->assertSame([$a->id, $z->id], $asc->pluck('id')->all());

        $desc = $this->indexResponse($user, ['order' => 'reference', 'order_direction' => 'desc'])->getData()['tickets'];
        $this->assertSame([$z->id, $a->id], $desc->pluck('id')->all());
    }

    public function testIndexSortsByType()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['draft' => false, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        $typeA = TicketType::factory()->create(['name' => 'AAA Type']);
        $typeZ = TicketType::factory()->create(['name' => 'ZZZ Type']);
        $ticketA = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $typeA->id]);
        $ticketZ = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_type_id' => $typeZ->id]);

        $asc = $this->indexResponse($user, ['order' => 'type', 'order_direction' => 'asc'])->getData()['tickets'];
        $this->assertSame([$ticketA->id, $ticketZ->id], $asc->pluck('id')->all());

        $desc = $this->indexResponse($user, ['order' => 'type', 'order_direction' => 'desc'])->getData()['tickets'];
        $this->assertSame([$ticketZ->id, $ticketA->id], $desc->pluck('id')->all());
    }

    public function testIndexSortsBySeat()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['draft' => false, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        $front = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);
        $back = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);
        Seat::factory()->create(['ticket_id' => $front->id, 'row' => 1, 'number' => 1]);
        Seat::factory()->create(['ticket_id' => $back->id, 'row' => 9, 'number' => 9]);

        $asc = $this->indexResponse($user, ['order' => 'seat', 'order_direction' => 'asc'])->getData()['tickets'];
        $this->assertSame([$front->id, $back->id], $asc->pluck('id')->all());

        $desc = $this->indexResponse($user, ['order' => 'seat', 'order_direction' => 'desc'])->getData()['tickets'];
        $this->assertSame([$back->id, $front->id], $desc->pluck('id')->all());
    }

    public function testIndexSortsByEventStartsAt()
    {
        $user = User::factory()->create();
        $oldEvent = Event::factory()->create(['draft' => false, 'starts_at' => now()->subDays(5), 'ends_at' => now()->subDays(5)->addHour()]);
        $newEvent = Event::factory()->create(['draft' => false, 'starts_at' => now()->addDays(5), 'ends_at' => now()->addDays(5)->addHour()]);
        $oldTicket = Ticket::factory()->create(['event_id' => $oldEvent->id, 'user_id' => $user->id]);
        $newTicket = Ticket::factory()->create(['event_id' => $newEvent->id, 'user_id' => $user->id]);

        $asc = $this->indexResponse($user, ['order' => 'event', 'order_direction' => 'asc'])->getData()['tickets'];
        $this->assertSame([$oldTicket->id, $newTicket->id], $asc->pluck('id')->all());
    }

    public function testIndexInvalidOrderFallsBackToNewestEventFirst()
    {
        $user = User::factory()->create();
        $oldEvent = Event::factory()->create(['draft' => false, 'starts_at' => now()->subDays(5), 'ends_at' => now()->subDays(5)->addHour()]);
        $newEvent = Event::factory()->create(['draft' => false, 'starts_at' => now()->addDays(5), 'ends_at' => now()->addDays(5)->addHour()]);
        $oldTicket = Ticket::factory()->create(['event_id' => $oldEvent->id, 'user_id' => $user->id]);
        $newTicket = Ticket::factory()->create(['event_id' => $newEvent->id, 'user_id' => $user->id]);

        $tickets = $this->indexResponse($user, ['order' => 'bogus', 'order_direction' => 'desc'])->getData()['tickets'];

        $this->assertSame([$newTicket->id, $oldTicket->id], $tickets->pluck('id')->all());
    }

    public function testIndexInvalidDirectionFallsBackToAscending()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['draft' => false, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        $a = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'reference' => 'AAA-001']);
        $z = Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'reference' => 'ZZZ-999']);

        $tickets = $this->indexResponse($user, ['order' => 'reference', 'order_direction' => 'sideways'])->getData()['tickets'];

        $this->assertSame([$a->id, $z->id], $tickets->pluck('id')->all());
    }

    public function testIndexPaginationPreservesSort()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['draft' => false, 'starts_at' => now(), 'ends_at' => now()->addHour()]);
        Ticket::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'reference' => 'AAA-001']);

        $tickets = $this->indexResponse($user, ['order' => 'reference', 'order_direction' => 'asc'])->getData()['tickets'];
        $url = $tickets->url(1);

        $this->assertStringContainsString('order=reference', $url);
        $this->assertStringContainsString('order_direction=asc', $url);
    }

    private function indexResponse(User $user, array $query = []): View
    {
        $this->actingAs($user);
        $controller = new TicketController();
        $request = Request::create('/', 'GET', $query);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $controller->index($request);
    }
}
