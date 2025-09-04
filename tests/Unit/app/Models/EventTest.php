<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\Event;
use App\Models\TicketProvider;
use App\Models\EventMapping;
use App\Models\TicketTypeMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DummyEvent extends Event
{
    public function toStringNamePublic(): string
    {
        return $this->toStringName();
    }
}

// Test provider implementations used by the event mapping tests.
use App\Services\Contracts\TicketProviderContract;


class TestProviderUnused implements TicketProviderContract
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
        return false;
    }
    public function syncTickets(string|\App\Models\EmailAddress $email): void {}
    public function getEvents(): array
    {
        return ['1' => 'E1'];
    }
    public function getTicketTypes(string $eventExternalId): array
    {
        return [];
    }
    public function syncAllTickets(?\Illuminate\Console\OutputStyle $output): void {}
}

class TestProviderUsed implements TicketProviderContract
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
        return false;
    }
    public function syncTickets(string|\App\Models\EmailAddress $email): void {}
    public function getEvents(): array
    {
        return ['2' => 'E2'];
    }
    public function getTicketTypes(string $eventExternalId): array
    {
        return [];
    }
    public function syncAllTickets(?\Illuminate\Console\OutputStyle $output): void {}
}

class TestProviderTT implements TicketProviderContract
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
        return false;
    }
    public function syncTickets(string|\App\Models\EmailAddress $email): void {}
    public function getEvents(): array
    {
        return [];
    }
    public function getTicketTypes(string $eventExternalId): array
    {
        return ['t1' => (object)['id' => 't1', 'name' => 'T1', 'used' => false]];
    }
    public function syncAllTickets(?\Illuminate\Console\OutputStyle $output): void {}
}


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
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $event->mappings());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $event->tickets());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $event->ticketTypes());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $event->seatingPlans());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $event->seatGroups());
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
            public function __construct($provider = null) {}
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
            public function __construct($provider = null) {}
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
        $providerUnused->provider_class = TestProviderUnused::class;
        $providerUsed->provider_class = TestProviderUsed::class;
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
            public function __construct($provider = null) {}
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
        app()->instance(TestProviderTT::class, $impl);
        $provider->provider_class = TestProviderTT::class;
        $provider->save();

        $event = Event::factory()->create();
        // Create a mapping so the provider has a providerEvent for this event
        \Database\Factories\EventMappingFactory::new()->create([
            'ticket_provider_id' => $provider->id,
            'event_id' => $event->id,
            'external_id' => 'ext1',
        ]);

        $result = $event->getAvailableTicketMappings(null);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals($provider->id, $result[0]->provider->id);
    }

    public function testProtectedToStringNameReturnsCode()
    {
        $dummy = new DummyEvent();
        $dummy->code = 'EVT-1';
        $this->assertEquals('EVT-1', $dummy->toStringNamePublic());
    }
}
