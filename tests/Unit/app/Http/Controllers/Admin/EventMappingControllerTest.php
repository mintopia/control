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

// small test provider used to exercise TicketProvider->getEvents() path
class TestProviderWithEvents implements \App\Services\Contracts\TicketProviderContract
{
    protected ?\App\Models\TicketProvider $provider;

    public function __construct(?\App\Models\TicketProvider $provider = null)
    {
        $this->provider = $provider;
    }

    public function configMapping(): array
    {
        return [];
    }

    public function install(): \App\Models\TicketProvider
    {
        return $this->provider ?? new \App\Models\TicketProvider();
    }

    public function processWebhook(\Illuminate\Http\Request $request): bool
    {
        return false;
    }

    public function syncTickets(string|\App\Models\EmailAddress $email): void
    {
        // noop for tests
    }

    public function getEvents(): array
    {
        // return an array keyed by external id so the controller foreach can find a match
        return ['54321' => 'Provider Event Name', '99999' => 'Other Event'];
    }

    public function getTicketTypes(string $eventExternalId): array
    {
        return [];
    }

    public function syncAllTickets(?\Illuminate\Console\OutputStyle $output): void
    {
        // noop
    }
}

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
        $provider->provider_class = TestProviderWithEvents::class;
        $provider->save();

        $mapping = EventMapping::factory()->for($event)->for($provider, 'provider')->create(['external_id' => '111']);

        $controller = new EventMappingController();
        $requestData = ['external_id' => "{$provider->id}:54321"];
        $response = $controller->update(\App\Http\Requests\Admin\EventMappingUpdateRequest::create('/', 'POST', $requestData), $event, $mapping);

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $mapping->refresh();
        $this->assertEquals('54321', $mapping->external_id);
        $this->assertEquals('Provider Event Name', $mapping->name);
    }
}
