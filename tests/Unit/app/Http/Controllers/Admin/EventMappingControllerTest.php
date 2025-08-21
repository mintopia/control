<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\EventMappingController;
use App\Models\Event;
use App\Models\EventMapping;
use App\Models\TicketProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventMappingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new EventMappingController();
        $this->assertInstanceOf(EventMappingController::class, $controller);
    }

    public function testStoreCreatesMappingAndRedirects()
    {
        $event = Event::factory()->create();
        $provider = TicketProvider::factory()->create();

        // external_id format: providerId:externalId (externalId can be any string/number here)
        $requestData = [
            'external_id' => "{$provider->id}:12345",
        ];

        $controller = new EventMappingController();
        $response = $controller->store(\App\Http\Requests\Admin\EventMappingUpdateRequest::create('/', 'POST', $requestData), $event);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseHas('event_mappings', ['event_id' => $event->id]);
    }

    public function testUpdateModifiesMappingAndRedirects()
    {
        $event = Event::factory()->create();
        $provider = TicketProvider::factory()->create();
        $mapping = EventMapping::factory()->for($event)->for($provider, 'provider')->create(['external_id' => '12345']);

        $controller = new EventMappingController();
        $requestData = ['external_id' => "{$provider->id}:54321"];
        $response = $controller->update(\App\Http\Requests\Admin\EventMappingUpdateRequest::create('/', 'POST', $requestData), $event, $mapping);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseHas('event_mappings', ['id' => $mapping->id, 'external_id' => '54321']);
    }

    public function testDestroyDeletesMappingAndRedirects()
    {
        $event = Event::factory()->create();
        $provider = TicketProvider::factory()->create();
        $mapping = EventMapping::factory()->for($event)->for($provider, 'provider')->create(['external_id' => '123']);

        $controller = new EventMappingController();
        $response = $controller->destroy($event, $mapping);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseMissing('event_mappings', ['id' => $mapping->id]);
    }
}
