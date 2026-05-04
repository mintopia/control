<?php

namespace Tests\Unit\app\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\TicketController;
use App\Models\ApiKey;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class TicketControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_tickets()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        Ticket::factory()->count(3)->create(['event_id' => $event->id]);

        $controller = new TicketController;
        $request = Request::create('/api/v1/tickets', 'GET', ['perPage' => 2]);
        $this->setApiKeyOnRequest($request);

        $response = $controller->index($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $body = $response->getData(true);
        $this->assertCount(2, $body['data']);
    }

    public function test_index_filter_by_event_code_scopes_results()
    {
        $eventA = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $eventB = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        Ticket::factory()->count(2)->create(['event_id' => $eventA->id]);
        Ticket::factory()->count(3)->create(['event_id' => $eventB->id]);

        $controller = new TicketController;
        $request = Request::create('/api/v1/tickets', 'GET', ['event' => $eventA->code]);
        $this->setApiKeyOnRequest($request);

        $body = $controller->index($request)->getData(true);
        $this->assertCount(2, $body['data']);
    }

    public function test_index_unknown_event_code_returns_empty_dataset()
    {
        Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        $controller = new TicketController;
        $request = Request::create('/api/v1/tickets', 'GET', ['event' => 'does-not-exist']);
        $this->setApiKeyOnRequest($request);

        $body = $controller->index($request)->getData(true);
        $this->assertCount(0, $body['data']);
    }

    public function test_index_includes_user_and_seat_in_payload()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $type = TicketType::factory()->create(['event_id' => $event->id]);
        Ticket::factory()->create([
            'event_id' => $event->id,
            'ticket_type_id' => $type->id,
        ]);

        $controller = new TicketController;
        $request = Request::create('/api/v1/tickets', 'GET');
        $this->setApiKeyOnRequest($request);

        $body = $controller->index($request)->getData(true);
        $this->assertArrayHasKey('user', $body['data'][0]);
        $this->assertArrayHasKey('seat', $body['data'][0]);
        $this->assertArrayHasKey('event', $body['data'][0]);
        $this->assertArrayHasKey('type', $body['data'][0]);
        $this->assertArrayHasKey('provider', $body['data'][0]);
    }

    protected function setApiKeyOnRequest(Request $request): ApiKey
    {
        $apiKey = ApiKey::factory()->create();
        $request->setUserResolver(fn () => $apiKey);

        return $apiKey;
    }
}
