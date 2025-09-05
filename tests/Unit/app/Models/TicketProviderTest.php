<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\TicketProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $provider->tickets());
    }

    public function testEventsRelationship()
    {
        $provider = new TicketProvider();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $provider->events());
    }

    public function testTypesRelationship()
    {
        $provider = new TicketProvider();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $provider->types());
    }

    public function testSettingsRelationship()
    {
        $provider = new TicketProvider();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class, $provider->settings());
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
        $mockEvent = $this->createMock(\App\Models\Event::class);
        $this->assertEquals([], $provider->getTicketTypes($mockEvent));
    }

    public function testGetSettingReturnsCachedValue()
    {
        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['settings'])
            ->getMock();
        // set the protected property properly via reflection so the class sees it
        $rp = new \ReflectionProperty(TicketProvider::class, '_settings');
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
        $mockProvider = $this->createMock(\App\Services\Contracts\TicketProviderContract::class);
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
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('toStringName');
        $method->setAccessible(true);
        $this->assertEquals('test_code', $method->invoke($provider));
    }

    // Stub provider class for tests that return array entries instead of objects
    public function testGetTicketTypesHandlesArrayEntries()
    {
        $provider = TicketProvider::factory()->create(['enabled' => 1]);

        // Create an event and a provider event mapping so getTicketTypes will be invoked
        $event = \App\Models\Event::factory()->create();
        \Database\Factories\EventMappingFactory::new()->create([
            'ticket_provider_id' => $provider->id,
            'event_id' => $event->id,
            'external_id' => 'e1',
        ]);

        // Bind a provider implementation that returns an indexed array of arrays (not objects)
        $impl = new class implements \App\Services\Contracts\TicketProviderContract {
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
                return ['t1' => (object)['id' => 't1', 'name' => 'T1']];
            }
            public function syncAllTickets(?\Illuminate\Console\OutputStyle $output): void {}
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

    public function testGetTicketTypesHandlesAssociativeMapping()
    {
        $provider = TicketProvider::factory()->create(['enabled' => 1]);

        // Create an event and a provider event mapping so getTicketTypes will be invoked
        $event = \App\Models\Event::factory()->create();
        \Database\Factories\EventMappingFactory::new()->create([
            'ticket_provider_id' => $provider->id,
            'event_id' => $event->id,
            'external_id' => 'e2',
        ]);

        // Bind a provider implementation that returns an associative mapping id=>name
        $impl = new class implements \App\Services\Contracts\TicketProviderContract {
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
                return ['t2' => 'T2'];
            }
            public function syncAllTickets(?\Illuminate\Console\OutputStyle $output): void {}
        };

        app()->instance(get_class($impl), $impl);
        $provider->provider_class = get_class($impl);
        $provider->save();

        $result = $provider->getTicketTypes($event);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertEquals('t2', $result[0]->id);
        $this->assertEquals('T2', $result[0]->name);
    }

    public function testProcessWebhookDelegatesToProvider()
    {
        $mockProvider = $this->createMock(\App\Services\Contracts\TicketProviderContract::class);
        $mockProvider->expects($this->once())->method('processWebhook')->willReturn(true);

        $provider = $this->getMockBuilder(TicketProvider::class)
            ->onlyMethods(['getProvider'])
            ->getMock();
        $provider->method('getProvider')->willReturn($mockProvider);

        $req = \Illuminate\Http\Request::create('/webhook', 'POST');
        $this->assertTrue($provider->processWebhook($req));
    }
}
