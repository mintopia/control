<?php

namespace Tests\Unit\app\Models;

use App\Models\Event;
use App\Models\TicketProvider;
use App\Models\TicketTypeMapping;
use Database\Factories\EventMappingFactory;
use Database\Factories\TicketTypeFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Test provider implementations used by the event mapping tests.


class EventTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateEvent()
    {
        $event = new Event();
        $this->assertInstanceOf(Event::class, $event);
    }

    public function testRelationships()
    {
        $event = new Event();
        $this->assertInstanceOf(HasMany::class, $event->mappings());
        $this->assertInstanceOf(HasMany::class, $event->tickets());
        $this->assertInstanceOf(HasMany::class, $event->ticketTypes());
        $this->assertInstanceOf(HasMany::class, $event->seatingPlans());
        $this->assertInstanceOf(HasMany::class, $event->seatGroups());
    }

    public function testGetRouteKeyNameReturnsCode()
    {
        $event = new Event();
        $this->assertEquals('code', $event->getRouteKeyName());
    }

    public function testGetAvailableEventMappingsFiltersUsedAndExisting()
    {
        // Create two providers: one with an unused event and one with a used event
        $providerUnused = TicketProvider::factory()->create(['enabled' => 1]);
        $providerUsed = TicketProvider::factory()->create(['enabled' => 1]);

        // Create anonymous provider implementations and bind to container
        $implUnused = new class {
            public function __construct($provider = null)
            {
            }

            public function getEvents()
            {
                return ['1' => (object)['id' => '1', 'name' => 'E1', 'used' => false]];
            }

            public function getTicketTypes($event)
            {
                return [];
            }
        };
        $implUsed = new class {
            public function __construct($provider = null)
            {
            }

            public function getEvents()
            {
                return ['2' => (object)['id' => '2', 'name' => 'E2', 'used' => true]];
            }

            public function getTicketTypes($event)
            {
                return [];
            }
        };

        // Point provider_class at our test provider classes so app()->make() instantiates them
        $providerUnused->provider_class = HelperClasses\TestProviderUnused::class;
        $providerUsed->provider_class = HelperClasses\TestProviderUsed::class;
        $providerUnused->save();
        $providerUsed->save();

        $event = Event::factory()->create();

        $result = $event->getAvailableEventMappings(null);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals($providerUnused->id, $result[0]->provider->id);
    }

    public function testGetAvailableTicketMappingsFiltersUsedAndExisting()
    {
        $provider = TicketProvider::factory()->create(['enabled' => 1]);
        $impl = new class {
            public function __construct($provider = null)
            {
            }

            public function getEvents()
            {
                return [];
            }

            public function getTicketTypes($event)
            {
                return ['t1' => (object)['id' => 't1', 'name' => 'T1', 'used' => false]];
            }
        };
        // Bind the anonymous implementation to the TestProviderTT class name so app()->make() returns it
        app()->instance(HelperClasses\TestProviderTT::class, $impl);
        $provider->provider_class = HelperClasses\TestProviderTT::class;
        $provider->save();

        $event = Event::factory()->create();
        // Create a mapping so the provider has a providerEvent for this event
        EventMappingFactory::new()->create([
            'ticket_provider_id' => $provider->id,
            'event_id' => $event->id,
            'external_id' => 'ext1',
        ]);

        $result = $event->getAvailableTicketMappings(null);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals($provider->id, $result[0]->provider->id);
    }

    public function testProtectedFunctionToStringName()
    {
        $event = new Event();
        $event->code = 'EVT-1';

        $reflection = new \ReflectionClass($event);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $result = $method->invoke($event);

        $this->assertEquals($event->code, $result);
    }

    public function testGetAvailableEventMappingsIncludesUsedWhenExistingProvided()
    {
        $provider = TicketProvider::factory()->create(['enabled' => 1]);
        // Use the TestProviderUsed implementation which returns a used event with id '2'
        $provider->provider_class = HelperClasses\TestProviderUsed::class;
        $provider->save();

        $event = Event::factory()->create();

        // Create an existing mapping that points to the provider and the used external id
        $existing = EventMappingFactory::new()->create([
            'ticket_provider_id' => $provider->id,
            'event_id' => $event->id,
            'external_id' => '2',
        ]);

        $result = $event->getAvailableEventMappings($existing);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        // The provider should be present because the existing mapping matches the used event
        $this->assertEquals($provider->id, $result[0]->provider->id);
        $this->assertEquals('2', $result[0]->events[0]->id);
    }

    public function testGetAvailableTicketMappingsIncludesUsedWhenExistingProvided()
    {
        $provider = TicketProvider::factory()->create(['enabled' => 1]);

        // Bind a simple implementation that returns a used ticket type
        app()->instance(HelperClasses\TestProviderTypesUsed::class, new HelperClasses\TestProviderTypesUsed());
        $provider->provider_class = HelperClasses\TestProviderTypesUsed::class;
        $provider->save();

        $event = Event::factory()->create();

        // Create an event mapping so the provider has a providerEvent for this event
        EventMappingFactory::new()->create([
            'ticket_provider_id' => $provider->id,
            'event_id' => $event->id,
            'external_id' => 'evt1',
        ]);

        // Create a ticket type mapping that matches the provider and external id 'tX'
        $created = TicketTypeMapping::create([
            'ticket_type_id' => TicketTypeFactory::new()->create(['event_id' => $event->id])->id,
            'ticket_provider_id' => $provider->id,
            'external_id' => 'tX',
        ]);

        // Pass the existing mapping so used types are allowed
        $result = $event->getAvailableTicketMappings($created);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals($provider->id, $result[0]->provider->id);
        $this->assertEquals('tX', $result[0]->types[0]->id);
    }

    public function testGetAvailableEventMappingsSkipsProvidersWithNoEvents()
    {
        $provider = TicketProvider::factory()->create(['enabled' => 1]);

        // Bind a provider implementation that returns no events
        app()->instance(HelperClasses\TestProviderTT::class, new HelperClasses\TestProviderTT());
        $provider->provider_class = HelperClasses\TestProviderTT::class;
        $provider->save();

        $event = Event::factory()->create();

        $result = $event->getAvailableEventMappings(null);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAvailableTicketMappingsSkipsProvidersWhenAllTypesFilteredOut()
    {
        $provider = TicketProvider::factory()->create(['enabled' => 1]);

        // Bind a provider implementation that returns only "used" types
        app()->instance(HelperClasses\TestProviderTypesUsed::class, new HelperClasses\TestProviderTypesUsed());
        $provider->provider_class = HelperClasses\TestProviderTypesUsed::class;
        $provider->save();

        $event = Event::factory()->create();

        $result = $event->getAvailableTicketMappings(null);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAvailableEventMappingsReturnsEmptyWhenAllProviderEventsAreUsed()
    {
        $provider = TicketProvider::factory()->create(['enabled' => 1]);
        // Use the TestProviderUsed which returns an event with id '2'
        $provider->provider_class = HelperClasses\TestProviderUsed::class;
        $provider->save();

        $event = Event::factory()->create();

        // Create an EventMapping so the provider marks external id '2' as used
        EventMappingFactory::new()->create([
            'ticket_provider_id' => $provider->id,
            'event_id' => $event->id,
            'external_id' => '2',
        ]);

        $result = $event->getAvailableEventMappings(null);
        $this->assertIsArray($result);
        // All provider events are 'used' and no existing mapping provided, so nothing should be returned
        $this->assertEmpty($result);
    }

    public function testGetAvailableTicketMappingsReturnsEmptyWhenAllProviderTypesAreUsed()
    {
        $provider = TicketProvider::factory()->create(['enabled' => 1]);
        // Bind a provider implementation that returns a used ticket type id 'tX'
        app()->instance(HelperClasses\TestProviderTypesUsed::class, new HelperClasses\TestProviderTypesUsed());
        $provider->provider_class = HelperClasses\TestProviderTypesUsed::class;
        $provider->save();

        $event = Event::factory()->create();

        // Create an event mapping so the provider has a providerEvent for this event
        EventMappingFactory::new()->create([
            'ticket_provider_id' => $provider->id,
            'event_id' => $event->id,
            'external_id' => 'evt1',
        ]);

        // Create a ticket type for this event and map it to the provider with external id 'tX' to mark it used
        $ticketType = TicketTypeFactory::new()->create(['event_id' => $event->id]);
        TicketTypeMapping::create([
            'ticket_type_id' => $ticketType->id,
            'ticket_provider_id' => $provider->id,
            'external_id' => 'tX',
        ]);

        $result = $event->getAvailableTicketMappings(null);
        $this->assertIsArray($result);
        // All provider types are considered 'used' and no existing mapping provided, so nothing should be returned
        $this->assertEmpty($result);
    }
}
