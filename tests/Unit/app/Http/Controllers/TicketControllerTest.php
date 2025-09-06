<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\TicketController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Setting;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Event;

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
        $request = \Illuminate\Http\Request::create('/', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $controller->index($request);
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
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
        $request = \Illuminate\Http\Request::create('/', 'GET');
        $response = $controller->show($request, $ticket);
        $this->assertInstanceOf(\Illuminate\View\View::class, $response);
        $data = $response->getData();
        $this->assertArrayHasKey('ticket', $data);
        $this->assertEquals($ticket->id, $data['ticket']->id);
    }

    public function testUpdateGenerateAndRemoveTransferCode()
    {
        $ticket = Ticket::factory()->create(['event_id' => Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()])->id]);
        $controller = new TicketController();

        // generate
        $request = \Illuminate\Http\Request::create('/', 'POST', ['generate' => '1']);
        $response = $controller->update($request, $ticket);
        $this->assertStringContainsString('/tickets/' . $ticket->id, $response->getTargetUrl());

        // remove
        $ticket->transfer_code = 'abc123';
        $ticket->save();
        $request = \Illuminate\Http\Request::create('/', 'POST', ['remove' => '1']);
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
        $request = \App\Http\Requests\TicketTransferRequest::create('/', 'POST', ['code' => 'tx-1']);
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
        $request = \Illuminate\Http\Request::create('/', 'POST', []);
        $response = $controller->update($request, $ticket);
        $this->assertStringContainsString('/tickets/' . $ticket->id, $response->getTargetUrl());
    }

    public function testUpdateWhenTransfersDisabledReturnsError()
    {
        Setting::create(['code' => 'disable-ticket-transfers', 'name' => 'Disable Transfers', 'value' => 1]);
        $ticket = Ticket::factory()->create(['event_id' => Event::factory()->create(['starts_at' => now(), 'ends_at' => now()->addHour()])->id]);
        $controller = new TicketController();
        $request = \Illuminate\Http\Request::create('/', 'POST', ['generate' => '1']);
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
        $request = \App\Http\Requests\TicketTransferRequest::create('/', 'POST', ['code' => 'tx-2']);
        $request->setUserResolver(function () use ($recipient) {
            return $recipient;
        });

        $controller = new TicketController();
        $response = $controller->transfer($request);
        $this->assertStringContainsString('/tickets', $response->getTargetUrl());
        $this->assertNotEmpty($response->getSession()->get('errorMessage'));
    }
}
