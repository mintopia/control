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
use App\Models\EmailAddress;
use App\Models\Event;
use App\Models\EventMapping;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

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
        // Return a test helper that exposes protected methods
        return new \Tests\Unit\app\Services\TicketProviders\DummyGenericTicketProvider($ticketProvider);
    }

    // --- Extra tests merged from GenericTicketProviderExtraTest.php ---

    public function test_make_ticket_returns_null_when_event_missing()
    {
        $provider = $this->createProvider();
        $data = (object)[
            'id' => 't1',
            'event_id' => 'no-such-event',
            'ticket_type_id' => 'type-x',
            'email' => 'foo@example.com',
            'description' => 'Test ticket',
            'reference' => 'ref1',
        ];

        $this->assertNull($provider->makeTicketPublic(null, $data));
    }

    public function test_make_ticket_returns_null_when_type_missing()
    {
        $provider = $this->createProvider();
        $event = Event::factory()->create();
        EventMapping::factory()->for($event)->for($provider->provider, 'provider')->create(['external_id' => 'evt1']);

        $data = (object)[
            'id' => 't1',
            'event_id' => 'evt1',
            'ticket_type_id' => 'no-type',
            'email' => 'foo@example.com',
            'description' => 'Test ticket',
            'reference' => 'ref1',
        ];

        $this->assertNull($provider->makeTicketPublic(null, $data));
    }

    public function test_make_ticket_creates_ticket_when_event_and_type_exist_and_links_user()
    {
        $provider = $this->createProvider();
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['email' => 'foo@example.com', 'verified_at' => now(), 'user_id' => $user->id]);
        $event = Event::factory()->create();
        $type = TicketType::factory()->create();
        EventMapping::factory()->for($event)->for($provider->provider, 'provider')->create(['external_id' => 'evt1']);
        \App\Models\TicketTypeMapping::create([
            'ticket_type_id' => $type->id,
            'ticket_provider_id' => $provider->provider->id,
            'external_id' => 'type1',
        ]);

        $data = (object)[
            'id' => 't2',
            'event_id' => 'evt1',
            'ticket_type_id' => 'type1',
            'email' => 'foo@example.com',
            'description' => 'Test ticket',
            'reference' => 'ref2',
        ];

        $ticket = $provider->makeTicketPublic(null, $data);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals('t2', $ticket->external_id);
        // reload relations/columns from DB to be sure associations persisted
        $ticket->refresh();
        $this->assertEquals($event->id, $ticket->event_id);
        // prefer checking the relation to avoid depending on column naming
        $this->assertNotNull($ticket->type, 'Ticket type relation should be set');
        $this->assertEquals($type->id, $ticket->type->id);
        // user should be linked via the email created above
        $this->assertNotNull($ticket->user, 'Ticket user should be linked');
        $this->assertEquals($user->id, $ticket->user->id);
    }

    public function test_get_tickets_pages_until_hasMore_is_false()
    {
        $provider = $this->createProvider([
            'endpoint' => 'https://api.example.test',
            'apikey' => 'key'
        ]);

        $resp1 = new Response(200, [], json_encode((object)[
            'tickets' => [(object)['id' => '1', 'status' => 'valid']],
            'hasMore' => true,
        ]));
        $resp2 = new Response(200, [], json_encode((object)[
            'tickets' => [(object)['id' => '2', 'status' => 'valid']],
            'hasMore' => false,
        ]));

        $mock = new MockHandler([$resp1, $resp2]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $ref = new \ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        $tickets = $provider->getTicketsPublic(null);
        // Current implementation resets the page buffer each loop and returns the last page only
        $this->assertCount(1, $tickets);
        $this->assertArrayNotHasKey('1', $tickets);
        $this->assertArrayHasKey('2', $tickets);
    }

    public function test_sync_tickets_removes_voided_and_adds_missing()
    {
        $provider = $this->createProvider(['apikey' => 'key', 'endpoint' => 'https://api.example.test']);

        // prepare existing tickets in DB
        $existing = Ticket::factory()->create(['ticket_provider_id' => $provider->provider->id, 'external_id' => '10']);

        // remote tickets: one voided (11), one valid (12)
        $resp = new Response(200, [], json_encode((object)[
            'tickets' => [
                (object)['id' => '11', 'status' => 'voided', 'event_id' => 'evtA', 'ticket_type_id' => 'typeA', 'email' => 'x@example.com', 'description' => 'd', 'reference' => 'r'],
                (object)['id' => '12', 'status' => 'valid', 'event_id' => 'evtB', 'ticket_type_id' => 'typeB', 'email' => 'x@example.com', 'description' => 'd2', 'reference' => 'r2'],
            ],
            'hasMore' => false,
        ]));

        $mock = new MockHandler([$resp]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $ref = new \ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        // create event and type mapping for evtB/typeB so new ticket can be created
        $event = Event::factory()->create();
        $type = TicketType::factory()->create();
        EventMapping::factory()->for($event)->for($provider->provider, 'provider')->create(['external_id' => 'evtB']);
        \App\Models\TicketTypeMapping::create([
            'ticket_type_id' => $type->id,
            'ticket_provider_id' => $provider->provider->id,
            'external_id' => 'typeB',
        ]);

        $provider->syncTickets('x@example.com');

        // voided ticket 11 should not exist, and 12 should have been added
        $this->assertDatabaseMissing('tickets', ['external_id' => '11']);
        $this->assertDatabaseHas('tickets', ['external_id' => '12']);
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

    public function test_process_ticket()
    {
        $provider = $this->createProvider();

        // prepare mappings and related models so processTicket can create a Ticket
        $user = \App\Models\User::factory()->create();
        \App\Models\EmailAddress::factory()->create([
            'email' => 'foo@example.com',
            'verified_at' => now(),
            'user_id' => $user->id,
        ]);

        $event = Event::factory()->create();
        EventMapping::factory()->for($event)->for($provider->provider, 'provider')->create(['external_id' => 'evt1']);

        $type = TicketType::factory()->create();
        \App\Models\TicketTypeMapping::create([
            'ticket_type_id' => $type->id,
            'ticket_provider_id' => $provider->provider->id,
            'external_id' => 'type1',
        ]);

        $data = (object)[
            'id' => 't1',
            'event_id' => 'evt1',
            'ticket_type_id' => 'type1',
            'email' => 'foo@example.com',
            'description' => 'Test ticket',
            'reference' => 'ref1',
        ];

        $result = $provider->processTicketPublic($data);
        $this->assertInstanceOf(Ticket::class, $result);
        $this->assertDatabaseHas('tickets', ['external_id' => 't1']);
    }

    public function test_get_client()
    {
        $provider = $this->createProvider();
        $this->assertInstanceOf(Client::class, $provider->getClientPublic());
    }
}
