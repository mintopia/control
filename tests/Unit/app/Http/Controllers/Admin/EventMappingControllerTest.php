<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\EventMappingController;
use App\Http\Requests\Admin\EventMappingUpdateRequest;
use App\Models\Event;
use App\Models\EventMapping;
use App\Models\TicketProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Tests\TestCase;

// small test provider used to exercise TicketProvider->getEvents() path

class EventMappingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateController()
    {
        $controller = new EventMappingController();
        $this->assertInstanceOf(EventMappingController::class, $controller);
    }

    public function testCreateReturnsViewWithAvailableMappings()
    {
        $event = Event::factory()->create();
        $provider = TicketProvider::factory()->create();
        // use test provider so getEvents() returns items
        $provider->provider_class = HelperClasses\TestProviderWithEvents::class;
        $provider->save();

        $controller = new EventMappingController();
        $resp = $controller->create($event);

        $this->assertInstanceOf(View::class, $resp);
        $data = $resp->getData();
        $this->assertArrayHasKey('availableMappings', $data);
        $this->assertArrayHasKey('event', $data);
        $this->assertEquals($event->id, $data['event']->id);
        $this->assertArrayHasKey('mapping', $data);
        $this->assertInstanceOf(EventMapping::class, $data['mapping']);
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
        $response = $controller->store(EventMappingUpdateRequest::create('/', 'POST', $requestData), $event);

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
        $response = $controller->update(EventMappingUpdateRequest::create('/', 'POST', $requestData), $event, $mapping);

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

    public function testEditReturnsView()
    {
        $event = Event::factory()->create();
        $provider = TicketProvider::factory()->create();
        $mapping = EventMapping::factory()->for($event)->for($provider, 'provider')->create(['external_id' => '123']);

        $controller = new EventMappingController();
        $response = $controller->edit($event, $mapping);

        $this->assertInstanceOf(View::class, $response);
        $data = $response->getData();
        $this->assertArrayHasKey('mapping', $data);
        $this->assertEquals($mapping->id, $data['mapping']->id);
        $this->assertArrayHasKey('event', $data);
        $this->assertEquals($event->id, $data['event']->id);
    }

    public function testDeleteReturnsView()
    {
        $event = Event::factory()->create();
        $provider = TicketProvider::factory()->create();
        $mapping = EventMapping::factory()->for($event)->for($provider, 'provider')->create(['external_id' => '123']);

        $controller = new EventMappingController();
        $response = $controller->delete($event, $mapping);

        $this->assertInstanceOf(View::class, $response);
        $data = $response->getData();
        $this->assertArrayHasKey('mapping', $data);
        $this->assertEquals($mapping->id, $data['mapping']->id);
    }

    public function testUpdateObjectSetsNameFromProviderEvents()
    {
        $event = Event::factory()->create();

        $provider = TicketProvider::factory()->create();
        // point provider to our small test provider class so getEvents() returns a matching id
        $provider->provider_class = HelperClasses\TestProviderWithEvents::class;
        $provider->save();

        $mapping = EventMapping::factory()->for($event)->for($provider, 'provider')->create(['external_id' => '111']);

        $controller = new EventMappingController();
        $requestData = ['external_id' => "{$provider->id}:54321"];
        $response = $controller->update(EventMappingUpdateRequest::create('/', 'POST', $requestData), $event, $mapping);

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $mapping->refresh();
        $this->assertEquals('54321', $mapping->external_id);
        $this->assertEquals('Provider Event Name', $mapping->name);
    }
}
