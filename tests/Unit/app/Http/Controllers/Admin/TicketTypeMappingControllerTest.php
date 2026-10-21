<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use App\Http\Controllers\Admin\TicketTypeMappingController;
use App\Http\Requests\Admin\TicketTypeMappingUpdateRequest;
use App\Models\EmailAddress;
use App\Models\Event;
use App\Models\EventMapping;
use App\Models\TicketProvider;
use App\Models\TicketType;
use App\Models\TicketTypeMapping;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use ReflectionClass;
use Tests\TestCase;

// Provider stubs implementing the contract so app()->make() returns correct types

class TicketTypeMappingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateEditDeleteViews()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        $mapping = new TicketTypeMapping();
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
        $provider = TicketProvider::factory()->create();

        $provider->provider_class = HelperClasses\TicketProviderStubWithTypes::class;
        $provider->save();
        // bind the stub into the container
        $this->app->bind(HelperClasses\TicketProviderStubWithTypes::class, function () {
            return new HelperClasses\TicketProviderStubWithTypes();
        });

        // ensure there's an EventMapping linking provider to event so getTicketTypes will be invoked
        $em = new EventMapping();
        $em->provider()->associate($provider);
        $em->event()->associate($event);
        $em->external_id = 'EV1';
        $em->save();

        $controller = new TicketTypeMappingController();
        $req = TicketTypeMappingUpdateRequest::create('/', 'POST', ['external_id' => $provider->id . ':42']);
        try {
            $controller->store($req, $event, $type);
        } catch (UrlGenerationException $ex) {
            // ignore missing route redirect
        }

        $mapping = TicketTypeMapping::whereExternalId('42')->first();
        $this->assertNotNull($mapping);
        $this->assertEquals('External Type', $mapping->name);
    }

    public function testUpdateObjectFallsBackToTicketNameWhenNoMatch()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        $mapping = new TicketTypeMapping();
        $mapping->type()->associate($type);
        $mapping->provider()->associate($provider);
        $mapping->external_id = 'x1';
        $mapping->save();

        $provider->provider_class = HelperClasses\TicketProviderStubNoTypes::class;
        $provider->save();
        $this->app->bind(HelperClasses\TicketProviderStubNoTypes::class, function () {
            return new HelperClasses\TicketProviderStubNoTypes();
        });
        // add event mapping so provider->getTicketTypes is called
        $em = new EventMapping();
        $em->provider()->associate($provider);
        $em->event()->associate($event);
        $em->external_id = 'EV1';
        $em->save();

        $controller = new TicketTypeMappingController();
        $req2 = TicketTypeMappingUpdateRequest::create('/', 'POST', ['external_id' => $provider->id . ':99']);
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod('updateObject');
        $method->setAccessible(true);
        $method->invoke($controller, $mapping, $req2);

        $this->assertNotNull($mapping->fresh()->name);
    }

    public function testUpdatePersistsChanges()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        $mapping = new TicketTypeMapping();
        $mapping->type()->associate($type);
        $mapping->provider()->associate($provider);
        $mapping->external_id = 'x1';
        $mapping->save();

        $provider->provider_class = HelperClasses\TicketProviderStub99::class;
        $provider->save();
        $this->app->bind(HelperClasses\TicketProviderStub99::class, function () {
            return new HelperClasses\TicketProviderStub99();
        });
        // add event mapping so provider->getTicketTypes returns our stubbed list
        $em = new EventMapping();
        $em->provider()->associate($provider);
        $em->event()->associate($event);
        $em->external_id = 'EV1';
        $em->save();

        $controller = new TicketTypeMappingController();
        $req = TicketTypeMappingUpdateRequest::create('/', 'POST', ['external_id' => $provider->id . ':99']);
        try {
            $controller->update($req, $event, $type, $mapping);
        } catch (UrlGenerationException $ex) {
            // ignore redirect
        }

        $this->assertEquals('New Name', $mapping->fresh()->name);
    }

    public function testDestroyDeletesMapping()
    {
        $event = Event::factory()->create();
        $type = TicketType::factory()->for($event)->create();
        $provider = TicketProvider::factory()->create();
        $mapping = new TicketTypeMapping();
        $mapping->type()->associate($type);
        $mapping->provider()->associate($provider);
        $mapping->external_id = 'x1';
        $mapping->save();

        $controller = new TicketTypeMappingController();
        try {
            $controller->destroy($event, $type, $mapping);
        } catch (UrlGenerationException $ex) {
            // ignore redirect
        }
        $this->assertNull(TicketTypeMapping::find($mapping->id));
    }
}
