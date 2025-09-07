<?php

namespace Tests\Unit\app\Models;

use App\Models\EmailAddress;
use App\Models\Event;
use App\Models\TicketProvider;
use App\Services\Contracts\TicketProviderContract;
use Database\Factories\EventMappingFactory;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionProperty;
use Tests\TestCase;

class TicketProviderTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateTicketProvider()
    {
        $provider = new TicketProvider();
        $this->assertInstanceOf(TicketProvider::class, $provider);
    }

    public function testTicketsRelationship()
    {
        $provider = new TicketProvider();
        $this->assertInstanceOf(HasMany::class, $provider->tickets());
    }

    public function testEventsRelationship()
    {
        $provider = new TicketProvider();
        $this->assertInstanceOf(HasMany::class, $provider->events());
    }

    public function testTypesRelationship()
    {
        $provider = new TicketProvider();
        $this->assertInstanceOf(HasMany::class, $provider->types());
    }

    public function testSettingsRelationship()
    {
        $provider = new TicketProvider();
        $this->assertInstanceOf(MorphMany::class, $provider->settings());
    }

    public function testGetEventsReturnsEmptyIfNotEnabled()
    {
        $provider = new TicketProvider();
        $provider->enabled = false;
        $this->assertEquals([], $provider->getEvents());
    }

    public function testGetTicketTypesReturnsEmptyIfNotEnabled()
    {
        $provider = new TicketProvider();
        $provider->enabled = false;
        $mockEvent = $this->createMock(Event::class);
        $this->assertEquals([], $provider->getTicketTypes($mockEvent));
    }

    public function testGetSettingReturnsCachedValue()
    {
        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['settings'])
            ->getMock();
        // set the protected property properly via reflection so the class sees it
        $rp = new ReflectionProperty(TicketProvider::class, '_settings');
        $rp->setAccessible(true);
        $rp->setValue($provider, ['foo' => 'bar']);
        $this->assertEquals('bar', $provider->getSetting('foo'));
    }

    public function testClearCacheSetsCachePrefixAndSaves()
    {
        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['save'])
            ->getMock();
        $provider->expects($this->once())->method('save');
        $provider->clearCache();
        $this->assertNotEmpty($provider->cache_prefix);
    }

    public function testConfigMappingDelegatesToProvider()
    {
        $mockProvider = $this->createMock(TicketProviderContract::class);
        $mockProvider->expects($this->once())->method('configMapping')->willReturn(['foo' => 'bar']);
        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['getProvider'])
            ->getMock();
        $provider->method('getProvider')->willReturn($mockProvider);
        $this->assertEquals(['foo' => 'bar'], $provider->configMapping());
    }

    public function testToStringNameReturnsCode()
    {
        $provider = new TicketProvider();
        $provider->code = 'test_code';
        $reflection = new ReflectionClass($provider);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $this->assertEquals('test_code', $method->invoke($provider));
    }

    // Stub provider class for tests that return array entries instead of objects
    public function testGetTicketTypesHandlesArrayEntries()
    {
        $provider = TicketProvider::factory()->create(['enabled' => 1]);

        // Create an event and a provider event mapping so getTicketTypes will be invoked
        $event = Event::factory()->create();
        EventMappingFactory::new()->create([
            'ticket_provider_id' => $provider->id,
            'event_id' => $event->id,
            'external_id' => 'e1',
        ]);

        // Bind a provider implementation that returns an indexed array of arrays (not objects)
        $impl = new class implements TicketProviderContract {
            public function __construct(?TicketProvider $provider = null)
            {
            }

            public function configMapping(): array
            {
                return [];
            }

            public function install(): TicketProvider
            {
                return new TicketProvider();
            }

            public function processWebhook(Request $request): bool
            {
                return false;
            }

            public function syncTickets(string|EmailAddress $email): void
            {
            }

            public function getEvents(): array
            {
                return [];
            }

            public function getTicketTypes(string $eventExternalId): array
            {
                return ['t1' => 'T1'];
            }

            public function syncAllTickets(?OutputStyle $output): void
            {
            }
        };

        app()->instance(get_class($impl), $impl);
        $provider->provider_class = get_class($impl);
        $provider->save();

        $result = $provider->getTicketTypes($event);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals('t1', $result[0]->id);
        $this->assertEquals('T1', $result[0]->name);
    }

    public function testProcessWebhookDelegatesToProvider()
    {
        $mockProvider = $this->createMock(TicketProviderContract::class);
        $mockProvider->expects($this->once())->method('processWebhook')->willReturn(true);

        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['getProvider'])
            ->getMock();
        $provider->method('getProvider')->willReturn($mockProvider);

        $req = Request::create('/webhook', 'POST');
        $this->assertTrue($provider->processWebhook($req));
    }

    public function testGetEventsDelegatesAndMarksUsed()
    {
        $provider = TicketProvider::factory()->create(['enabled' => 1]);

        // Create an event mapping so the provider reports it as used
        $event = Event::factory()->create();
        EventMappingFactory::new()->create([
            'ticket_provider_id' => $provider->id,
            'event_id' => $event->id,
            'external_id' => 'external_e1',
        ]);

        // Bind an implementation that returns an associative mapping of events
        $impl = new class implements TicketProviderContract {
            public function __construct(?TicketProvider $provider = null)
            {
            }

            public function configMapping(): array
            {
                return [];
            }

            public function install(): TicketProvider
            {
                return new TicketProvider();
            }

            public function processWebhook(Request $request): bool
            {
                return false;
            }

            public function syncTickets(string|EmailAddress $email): void
            {
            }

            public function getEvents(): array
            {
                return ['external_e1' => 'Event One', 'external_e2' => 'Event Two'];
            }

            public function getTicketTypes(string $eventExternalId): array
            {
                return [];
            }

            public function syncAllTickets(?OutputStyle $output): void
            {
            }
        };

        app()->instance(get_class($impl), $impl);
        $provider->provider_class = get_class($impl);
        $provider->save();

        $result = $provider->getEvents();
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        // find the entry for external_e1
        $found = array_filter($result, fn($r) => $r->id === 'external_e1');
        $this->assertNotEmpty($found);
        $entry = array_values($found)[0];
        $this->assertTrue($entry->used);
        $this->assertNotEmpty($entry->used_by);
    }

    public function testGetTicketTypesReturnsEmptyWhenProviderReturnsFalsy()
    {
        $provider = TicketProvider::factory()->create(['enabled' => 1]);

        $event = Event::factory()->create();
        EventMappingFactory::new()->create([
            'ticket_provider_id' => $provider->id,
            'event_id' => $event->id,
            'external_id' => 'ext_empty',
        ]);

        $impl = new class implements TicketProviderContract {
            public function __construct(?TicketProvider $provider = null)
            {
            }

            public function configMapping(): array
            {
                return [];
            }

            public function install(): TicketProvider
            {
                return new TicketProvider();
            }

            public function processWebhook(Request $request): bool
            {
                return false;
            }

            public function syncTickets(string|EmailAddress $email): void
            {
            }

            public function getEvents(): array
            {
                return [];
            }

            public function getTicketTypes(string $eventExternalId): array
            {
                return [];
            }

            public function syncAllTickets(?OutputStyle $output): void
            {
            }
        };

        app()->instance(get_class($impl), $impl);
        $provider->provider_class = get_class($impl);
        $provider->save();

        $result = $provider->getTicketTypes($event);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
