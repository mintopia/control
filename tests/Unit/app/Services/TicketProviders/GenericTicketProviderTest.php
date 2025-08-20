<?php

namespace Tests\Unit\app\Services\TicketProviders;

use Tests\TestCase;
use App\Services\TicketProviders\GenericTicketProvider;
use App\Models\TicketProvider;
use App\Models\ProviderSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use GuzzleHttp\Psr7\Response;

class GenericTicketProviderTest extends TestCase
{
    use RefreshDatabase;

    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        // Default provider used by most tests
        $this->provider = $this->createProvider();
    }

    /**
     * Create a provider for tests. Use when tests need custom settings.
     */
    protected function createProvider(array $settings = [])
    {
        // Allow tests to override the provider code/name via keys in $settings.
        // If not provided, generate a unique code so multiple providers can be created
        // within the same test class without triggering UNIQUE constraint errors.
        $code = $settings['code'] ?? 'generic_' . substr(md5((string) microtime(true) . rand()), 0, 8);
        $name = $settings['_name'] ?? 'Generic Provider';

        $ticketProvider = TicketProvider::factory()->create([
            'name' => $name,
            'code' => $code,
            'provider_class' => GenericTicketProvider::class,
        ]);

        // Do not treat our internal override keys as ProviderSetting entries.
        $realSettings = $settings;
        unset($realSettings['code'], $realSettings['_name']);

        foreach ($realSettings as $sCode => $value) {
            ProviderSetting::factory()->create([
                'provider_id' => $ticketProvider->id,
                'code' => $sCode,
                'value' => $value,
            ]);
        }
        // Return an anonymous subclass that exposes the underlying TicketProvider model
        return new class($ticketProvider) extends GenericTicketProvider {
            public ?\App\Models\TicketProvider $provider = null;
            public function __construct(?\App\Models\TicketProvider $p = null)
            {
                parent::__construct($p);
                $this->provider = $p;
            }
        };
    }

    public function test_config_mapping_returns_expected_array()
    {
        $provider = $this->provider;
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('apikey', $mapping);
        $this->assertEquals('API Key', $mapping['apikey']->name);
        $this->assertTrue($mapping['apikey']->encrypted);
        $this->assertArrayHasKey('endpoint', $mapping);
        $this->assertEquals('Base URL', $mapping['endpoint']->name);
    }

    public function test_process_webhook_calls_process_ticket_and_returns_true()
    {
        $provider = $this->provider;
        $mock = new class($provider->provider) extends GenericTicketProvider {
            public function __construct(?\App\Models\TicketProvider $p = null)
            {
                parent::__construct($p);
            }
            protected function processTicket(object $payload): ?\App\Models\Ticket
            {
                return null;
            }
        };

        $request = Request::create('/webhook', 'POST', ['payload' => ['id' => 'abc']]);
        $this->assertTrue($mock->processWebhook($request));
    }

    public function test_get_events_returns_cached_data()
    {
        $provider = $this->provider;
        $key = "ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events";
        Cache::put($key, ['evt1' => 'Event 1'], 10);
        $events = $provider->getEvents();
        $this->assertEquals(['evt1' => 'Event 1'], $events);
    }

    public function test_get_events_fetches_from_api_and_caches()
    {
        $provider = $this->createProvider([
            'apikey' => 'key',
            'endpoint' => 'https://api.example.com',
        ]);
        Cache::forget("ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events");

        // Create a Guzzle client with a MockHandler that returns the expected events response
        $mockResponse = new Response(200, [], json_encode((object)[
            'events' => [
                (object)['id' => 'evt1', 'name' => 'Event 1'],
                (object)['id' => 'evt2', 'name' => 'Event 2'],
            ],
            'hasMore' => false,
        ]));
        $mock = new \GuzzleHttp\Handler\MockHandler([$mockResponse]);
        $handlerStack = \GuzzleHttp\HandlerStack::create($mock);
        $guzzleClient = new \GuzzleHttp\Client(['handler' => $handlerStack]);

        // Set the Guzzle client onto the provider (bypass visibility via reflection)
        $providerReflection = new \ReflectionClass($provider);
        $clientProp = $providerReflection->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($provider, $guzzleClient);

        $events = $provider->getEvents();
        $this->assertEquals(['evt1' => 'Event 1', 'evt2' => 'Event 2'], $events);

        // Should now be cached
        $cached = Cache::get("ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events");
        $this->assertEquals($events, $cached);
    }

    public function test_get_ticket_types_returns_cached_data()
    {
        $provider = $this->provider;
        $eventId = 'evt-1';
        $key = "ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events.{$eventId}.tickettypes";
        Cache::put($key, ['type1' => 'VIP'], 10);
        $types = $provider->getTicketTypes($eventId);
        $this->assertEquals(['type1' => 'VIP'], $types);
    }

    public function test_get_ticket_types_fetches_from_api_and_caches()
    {
        $provider = $this->createProvider([
            'apikey' => 'key',
            'endpoint' => 'https://api.example.com',
        ]);
        $eventId = 'evt-1';
        $key = "ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events.{$eventId}.tickettypes";
        Cache::forget($key);

        // Create a Guzzle client with a MockHandler that returns the expected ticket types response
        $mockResponse = new Response(200, [], json_encode((object)[
            'ticket_types' => [
                (object)['id' => 'type1', 'name' => 'VIP'],
                (object)['id' => 'type2', 'name' => 'Standard'],
            ],
        ]));
        $mock = new \GuzzleHttp\Handler\MockHandler([$mockResponse]);
        $handlerStack = \GuzzleHttp\HandlerStack::create($mock);
        $guzzleClient = new \GuzzleHttp\Client(['handler' => $handlerStack]);

        $providerReflection = new \ReflectionClass($provider);
        $clientProp = $providerReflection->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($provider, $guzzleClient);

        $types = $provider->getTicketTypes($eventId);
        $this->assertEquals(['type1' => 'VIP', 'type2' => 'Standard'], $types);

        // Should now be cached
        $cached = Cache::get($key);
        $this->assertEquals($types, $cached);
    }
}
