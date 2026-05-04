<?php

namespace Tests\Unit\app\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\EventController;
use App\Models\ApiKey;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_paginated_events_ordered_by_start_desc()
    {
        $older = Event::factory()->create([
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDays(9),
        ]);
        $newer = Event::factory()->create([
            'starts_at' => now()->addDays(1),
            'ends_at' => now()->addDays(2),
        ]);

        $controller = new EventController;
        $request = Request::create('/api/v1/events', 'GET', ['perPage' => 1]);
        $this->setApiKeyOnRequest($request);

        $response = $controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $body = $response->getData(true);
        $this->assertCount(1, $body['data']);
        // Newer event must come first
        $this->assertEquals($newer->code, $body['data'][0]['code']);
    }

    public function test_index_exposes_admin_fields_when_api_key_attached()
    {
        Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
            'draft' => true,
            'boxoffice_url' => 'https://bo.example.com',
        ]);

        $controller = new EventController;
        $request = Request::create('/api/v1/events', 'GET');
        $this->setApiKeyOnRequest($request);

        $body = $controller->index($request)->getData(true);
        $this->assertArrayHasKey('id', $body['data'][0]);
        $this->assertArrayHasKey('draft', $body['data'][0]);
        $this->assertEquals('https://bo.example.com', $body['data'][0]['boxoffice_url']);
    }

    public function test_show_returns_single_event_by_code()
    {
        $event = Event::factory()->create([
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        $controller = new EventController;
        $request = Request::create("/api/v1/events/{$event->code}", 'GET');
        $this->setApiKeyOnRequest($request);

        $response = $controller->show($request, $event);
        $body = $response->getData(true);
        $this->assertEquals($event->code, $body['data']['code']);
        $this->assertEquals($event->id, $body['data']['id']);
    }

    protected function setApiKeyOnRequest(Request $request): ApiKey
    {
        $apiKey = ApiKey::factory()->create();
        $request->setUserResolver(fn () => $apiKey);

        return $apiKey;
    }
}
