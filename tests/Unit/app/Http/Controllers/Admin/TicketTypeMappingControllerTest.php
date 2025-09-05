<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Admin\TicketTypeMappingController;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\TicketTypeMapping;

// Provider stubs implementing the contract so app()->make() returns correct types
class TicketProviderStubWithTypes implements \App\Services\Contracts\TicketProviderContract
{
    public function __construct(?\App\Models\TicketProvider $provider = null) {}
    public function configMapping(): array
    {
        return [];
    }
    public function install(): \App\Models\TicketProvider
    {
        return new \App\Models\TicketProvider();
    }
    public function processWebhook(\Illuminate\Http\Request $request): bool
    {
        return true;
    }
    public function syncTickets(string|\App\Models\EmailAddress $email): void {}
    public function getEvents(): array
    {
        return ['EV1' => 'E1'];
    }
    public function getTicketTypes(string $eventExternalId): array
    {
        return ['42' => 'External Type'];
    }
    public function syncAllTickets(?\Illuminate\Console\OutputStyle $output): void {}
}

class TicketProviderStubNoTypes implements \App\Services\Contracts\TicketProviderContract
{
    public function __construct(?\App\Models\TicketProvider $provider = null) {}
    public function configMapping(): array
    {
        return [];
    }
    public function install(): \App\Models\TicketProvider
    {
        return new \App\Models\TicketProvider();
    }
    public function processWebhook(\Illuminate\Http\Request $request): bool
    {
        return true;
    }
    public function syncTickets(string|\App\Models\EmailAddress $email): void {}
    public function getEvents(): array
    {
        return ['EV1' => 'E1'];
    }
    public function getTicketTypes(string $eventExternalId): array
    {
        return [];
    }
    public function syncAllTickets(?\Illuminate\Console\OutputStyle $output): void {}
}

class TicketProviderStub99 implements \App\Services\Contracts\TicketProviderContract
{
    public function __construct(?\App\Models\TicketProvider $provider = null) {}
    public function configMapping(): array
    {
        return [];
    }
    public function install(): \App\Models\TicketProvider
    {
        return new \App\Models\TicketProvider();
    }
    public function processWebhook(\Illuminate\Http\Request $request): bool
    {
        return true;
    }
    public function syncTickets(string|\App\Models\EmailAddress $email): void {}
    public function getEvents(): array
    {
        return ['EV1' => 'E1'];
    }
    public function getTicketTypes(string $eventExternalId): array
    {
        return ['99' => 'New Name'];
    }
    public function syncAllTickets(?\Illuminate\Console\OutputStyle $output): void {}
}

class TicketProviderStubObj implements \App\Services\Contracts\TicketProviderContract
{
    public function __construct(?\App\Models\TicketProvider $provider = null) {}
    public function configMapping(): array
    {
        return [];
    }
    public function install(): \App\Models\TicketProvider
    {
        return new \App\Models\TicketProvider();
    }
    public function processWebhook(\Illuminate\Http\Request $request): bool
    {
        return true;
    }
    public function syncTickets(string|\App\Models\EmailAddress $email): void {}
    public function getEvents(): array
    {
        return ['EV1' => 'E1'];
    }
    public function getTicketTypes(string $eventExternalId): array
    {
        return [(object)['id' => '11', 'name' => 'First'], (object)['id' => '22', 'name' => 'Second'], (object)['id' => '42', 'name' => 'Matched']];
    }
    public function syncAllTickets(?\Illuminate\Console\OutputStyle $output): void {}
}

class TicketTypeMappingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateEditDeleteViews()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = \App\Models\TicketProvider::factory()->create();
        $mapping = new \App\Models\TicketTypeMapping();
        $mapping->type()->associate($type);
        $mapping->provider()->associate($provider);
        $mapping->external_id = 'x1';
        $mapping->save();
        $c = new TicketTypeMappingController();
        $this->assertTrue(is_object($c->create($event, $type)));
        $this->assertTrue(is_object($c->edit($event, $type, $mapping)));
        $this->assertTrue(is_object($c->delete($event, $type, $mapping)));
    }

    public function testStoreCreatesMapping()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = \App\Models\TicketProvider::factory()->create();

        $provider->provider_class = TicketProviderStubWithTypes::class;
        $provider->save();
        // bind the stub into the container
        $this->app->bind(TicketProviderStubWithTypes::class, function () {
            return new TicketProviderStubWithTypes();
        });

        // ensure there's an EventMapping linking provider to event so getTicketTypes will be invoked
        $em = new \App\Models\EventMapping();
        $em->provider()->associate($provider);
        $em->event()->associate($event);
        $em->external_id = 'EV1';
        $em->save();

        $controller = new TicketTypeMappingController();
        $req = \App\Http\Requests\Admin\TicketTypeMappingUpdateRequest::create('/', 'POST', ['external_id' => $provider->id . ':42']);
        try {
            $controller->store($req, $event, $type);
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $ex) {
            // ignore missing route redirect
        }

        $mapping = \App\Models\TicketTypeMapping::whereExternalId('42')->first();
        $this->assertNotNull($mapping);
        $this->assertEquals('External Type', $mapping->name);
    }

    public function testUpdateObjectFallsBackToTicketNameWhenNoMatch()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = \App\Models\TicketProvider::factory()->create();
        $mapping = new \App\Models\TicketTypeMapping();
        $mapping->type()->associate($type);
        $mapping->provider()->associate($provider);
        $mapping->external_id = 'x1';
        $mapping->save();

        $provider->provider_class = TicketProviderStubNoTypes::class;
        $provider->save();
        $this->app->bind(TicketProviderStubNoTypes::class, function () {
            return new TicketProviderStubNoTypes();
        });
        // add event mapping so provider->getTicketTypes is called
        $em = new \App\Models\EventMapping();
        $em->provider()->associate($provider);
        $em->event()->associate($event);
        $em->external_id = 'EV1';
        $em->save();

        $controller = new TicketTypeMappingController();
        $req2 = \App\Http\Requests\Admin\TicketTypeMappingUpdateRequest::create('/', 'POST', ['external_id' => $provider->id . ':99']);
        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        $method->invoke($controller, $mapping, $req2);

        $this->assertNotNull($mapping->fresh()->name);
    }

    public function testUpdatePersistsChanges()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = \App\Models\TicketProvider::factory()->create();
        $mapping = new \App\Models\TicketTypeMapping();
        $mapping->type()->associate($type);
        $mapping->provider()->associate($provider);
        $mapping->external_id = 'x1';
        $mapping->save();

        $provider->provider_class = TicketProviderStub99::class;
        $provider->save();
        $this->app->bind(TicketProviderStub99::class, function () {
            return new TicketProviderStub99();
        });
        // add event mapping so provider->getTicketTypes returns our stubbed list
        $em = new \App\Models\EventMapping();
        $em->provider()->associate($provider);
        $em->event()->associate($event);
        $em->external_id = 'EV1';
        $em->save();

        $controller = new TicketTypeMappingController();
        $req = \App\Http\Requests\Admin\TicketTypeMappingUpdateRequest::create('/', 'POST', ['external_id' => $provider->id . ':99']);
        try {
            $controller->update($req, $event, $type, $mapping);
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $ex) {
            // ignore redirect
        }

        $this->assertEquals('New Name', $mapping->fresh()->name);
    }

    //VALIDATE Normalisation for Types in TicketProvider
    public function testUpdateObjectSelectsMatchingObjectTypeFromProvider()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = \App\Models\TicketProvider::factory()->create();
        $mapping = new \App\Models\TicketTypeMapping();
        $mapping->type()->associate($type);
        $mapping->provider()->associate($provider);
        $mapping->external_id = 'x1';
        $mapping->save();

        // use the concrete stub class that returns objects
        $provider->provider_class = TicketProviderStubObj::class;
        $provider->save();
        $this->app->bind(TicketProviderStubObj::class, function () {
            return new TicketProviderStubObj();
        });

        $controller = new TicketTypeMappingController();
        // ensure there's an EventMapping linking provider to event so getTicketTypes will be invoked
        $em = new \App\Models\EventMapping();
        $em->provider()->associate($provider);
        $em->event()->associate($event);
        $em->external_id = 'EV1';
        $em->save();
        $req2 = \App\Http\Requests\Admin\TicketTypeMappingUpdateRequest::create('/', 'POST', ['external_id' => $provider->id . ':42']);
        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        $method->invoke($controller, $mapping, $req2);

        $this->assertEquals('Matched', $mapping->fresh()->name);
    }

    public function testDestroyDeletesMapping()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = \App\Models\TicketProvider::factory()->create();
        $mapping = new \App\Models\TicketTypeMapping();
        $mapping->type()->associate($type);
        $mapping->provider()->associate($provider);
        $mapping->external_id = 'x1';
        $mapping->save();

        $controller = new TicketTypeMappingController();
        try {
            $controller->destroy($event, $type, $mapping);
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $ex) {
            // ignore redirect
        }
        $this->assertNull(\App\Models\TicketTypeMapping::find($mapping->id));
    }
}
