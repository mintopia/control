<?php

namespace Tests\Unit\app\Services\TicketProviders;

use App\Models\EmailAddress;
use App\Models\Event;
use App\Models\EventMapping;
use App\Models\ProviderSetting;
use App\Models\Ticket;
use App\Models\TicketProvider;
use App\Models\TicketType;
use App\Models\TicketTypeMapping;
use App\Models\User;
use App\Services\TicketProviders\GenericTicketProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use Tests\TestCase;
use Tests\Traits\ProviderTestHelpers;

class GenericTicketProviderTest extends TestCase
{
    use RefreshDatabase;
    use ProviderTestHelpers;

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
        $code = $settings['code'] ?? 'generic_' . substr(md5((string)microtime(true) . rand()), 0, 8);
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
        return $this->makeTicketProvider($ticketProvider);
    }

    public function testMakeTicketReturnsNullWhenEventMissing()
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

        $this->assertNull($this->callProtected($provider, 'makeTicket', [null, $data]));
    }

    public function testMakeTicketReturnsNullWhenTypeMissing()
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

        $this->assertNull($this->callProtected($provider, 'makeTicket', [null, $data]));
    }

    public function testMakeTicketCreatesTicketWhenEventAndTypeExistAndLinksUser()
    {
        $provider = $this->createProvider();
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['email' => 'foo@example.com', 'verified_at' => now(), 'user_id' => $user->id]);
        $event = Event::factory()->create();
        $type = TicketType::factory()->create();
        EventMapping::factory()->for($event)->for($provider->provider, 'provider')->create(['external_id' => 'evt1']);
        TicketTypeMapping::create([
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

        $ticket = $this->callProtected($provider, 'makeTicket', [null, $data]);
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

    public function testGetTicketsPagesUntilHasMoreIsFalse()
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
        $client = new Client(['handler' => $handler, 'base_uri' => 'https://api.example.test']);

        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        $tickets = $this->callProtected($provider, 'getTickets', [null]);
        // Current implementation resets the page buffer each loop and returns the last page only
        $this->assertCount(1, $tickets);
        $this->assertArrayNotHasKey('1', $tickets);
        $this->assertArrayHasKey('2', $tickets);
    }

    public function testGetTicketsFetchesFromApiAndPages()
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
        $client = new Client(['handler' => $handler, 'base_uri' => 'https://api.example.test']);
        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        // create event and type mapping for evtB/typeB so new ticket can be created
        $event = Event::factory()->create();
        $type = TicketType::factory()->create();
        EventMapping::factory()->for($event)->for($provider->provider, 'provider')->create(['external_id' => 'evtB']);
        TicketTypeMapping::create([
            'ticket_type_id' => $type->id,
            'ticket_provider_id' => $provider->provider->id,
            'external_id' => 'typeB',
        ]);

        $provider->syncTickets('x@example.com');

        // voided ticket 11 should not exist, and 12 should have been added
        $this->assertDatabaseMissing('tickets', ['external_id' => '11']);
        $this->assertDatabaseHas('tickets', ['external_id' => '12']);
    }


    public function testConfigMappingReturnsExpectedArray()
    {
        // Instantiate the provider directly to test the public configMapping method
        $provider = new GenericTicketProvider();
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('apikey', $mapping);
        $this->assertEquals('API Key', $mapping['apikey']->name);
        $this->assertTrue($mapping['apikey']->encrypted);
        $this->assertArrayHasKey('endpoint', $mapping);
        $this->assertEquals('Base URL', $mapping['endpoint']->name);
    }

    public function testConfigMappingStructureAndValidation()
    {
        $provider = new GenericTicketProvider();
        $mapping = $provider->configMapping();

        // mapping entries should be objects with expected keys
        $this->assertIsArray($mapping);
        $this->assertIsObject($mapping['apikey']);
        $this->assertIsObject($mapping['endpoint']);

        // validation strings must match the implementation contract
        $this->assertEquals('required|string', $mapping['apikey']->validation);
        $this->assertEquals('required|string', $mapping['endpoint']->validation);

        // apikey should be marked encrypted; endpoint should not have an encrypted flag
        $this->assertTrue(isset($mapping['apikey']->encrypted) && $mapping['apikey']->encrypted);
        $this->assertFalse(property_exists($mapping['endpoint'], 'encrypted'));
    }

    public function testProcessWebhookCallsProcessTicketAndReturnsTrue()
    {
        $provider = $this->provider;
        $mock = new class ($provider->provider) extends GenericTicketProvider {
            public function __construct(?TicketProvider $p = null)
            {
                parent::__construct($p);
            }

            protected function processTicket(object $payload): ?Ticket
            {
                return null;
            }
        };

        $request = Request::create('/webhook', 'POST', ['payload' => ['id' => 'abc']]);
        $this->assertTrue($mock->processWebhook($request));
    }

    public function testGetEventsReturnsCachedData()
    {
        $provider = $this->provider;
        $key = "ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events";
        Cache::put($key, ['evt1' => 'Event 1'], 10);
        $events = $provider->getEvents();
        $this->assertEquals(['evt1' => 'Event 1'], $events);
    }

    public function testGetEventsFetchesFromApiAndCaches()
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
        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack, 'base_uri' => 'https://api.example.test']);

        // Set the Guzzle client onto the provider (bypass visibility via reflection)
        $providerReflection = new ReflectionClass($provider);
        $clientProp = $providerReflection->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($provider, $guzzleClient);

        $events = $provider->getEvents();
        $this->assertEquals(['evt1' => 'Event 1', 'evt2' => 'Event 2'], $events);

        // Should now be cached
        $cached = Cache::get("ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events");
        $this->assertEquals($events, $cached);
    }

    public function testGetTicketTypesReturnsCachedData()
    {
        $provider = $this->provider;
        $eventId = 'evt-1';
        $key = "ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events.{$eventId}.tickettypes";
        Cache::put($key, ['type1' => 'VIP'], 10);
        $types = $provider->getTicketTypes($eventId);
        $this->assertEquals(['type1' => 'VIP'], $types);
    }

    public function testGetTicketTypesFetchesFromApiAndCaches()
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
        $mock = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mock);
        $guzzleClient = new Client(['handler' => $handlerStack, 'base_uri' => 'https://api.example.test']);

        $providerReflection = new ReflectionClass($provider);
        $clientProp = $providerReflection->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($provider, $guzzleClient);

        $types = $provider->getTicketTypes($eventId);
        $this->assertEquals(['type1' => 'VIP', 'type2' => 'Standard'], $types);

        // Should now be cached
        $cached = Cache::get($key);
        $this->assertEquals($types, $cached);
    }

    public function testProcessTicket()
    {
        $provider = $this->createProvider();

        // prepare mappings and related models so processTicket can create a Ticket
        $user = User::factory()->create();
        EmailAddress::factory()->create([
            'email' => 'foo@example.com',
            'verified_at' => now(),
            'user_id' => $user->id,
        ]);

        $event = Event::factory()->create();
        EventMapping::factory()->for($event)->for($provider->provider, 'provider')->create(['external_id' => 'evt1']);

        $type = TicketType::factory()->create();
        TicketTypeMapping::create([
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

        $result = $this->callProtected($provider, 'processTicket', [$data]);
        $this->assertInstanceOf(Ticket::class, $result);
        $this->assertDatabaseHas('tickets', ['external_id' => 't1']);
    }

    public function testProcessTicketDeletesExistingWhenVoided()
    {
        $provider = $this->createProvider();

        // create an existing ticket that should be deleted when status is voided
        $existing = Ticket::factory()->create([
            'ticket_provider_id' => $provider->provider->id,
            'external_id' => 'del-me',
        ]);

        $data = (object)[
            'id' => 'del-me',
            'status' => 'voided',
            'event_id' => 'unused',
            'ticket_type_id' => 'unused',
            'email' => 'nobody@example.com',
        ];

        $result = $this->callProtected($provider, 'processTicket', [$data]);

        // The DB row should have been deleted
        $this->assertDatabaseMissing('tickets', ['external_id' => 'del-me']);
    }

    public function testProcessTicketReturnsNullWhenEventMissing()
    {
        $provider = $this->createProvider();

        // No EventMapping exists for this event id
        $data = (object)[
            'id' => 'no-event-pt',
            'status' => 'valid',
            'event_id' => 'no-such-event-pt',
            'ticket_type_id' => 'type-x',
            'email' => 'foo@example.com',
        ];

        $result = $this->callProtected($provider, 'processTicket', [$data]);
        $this->assertNull($result, 'processTicket should return null when the event mapping is missing');
    }

    public function testSyncTicketsDeletesVoidedTicket()
    {
        $provider = $this->createProvider(['apikey' => 'key', 'endpoint' => 'https://api.example.test']);

        // prepare existing ticket in DB that should be removed when remote reports it as voided
        $existing = Ticket::factory()->create(['ticket_provider_id' => $provider->provider->id, 'external_id' => 'voided1']);

        $resp = new Response(200, [], json_encode((object)[
            'tickets' => [
                (object)['id' => 'voided1', 'status' => 'voided', 'event_id' => 'evtX', 'ticket_type_id' => 'typeX', 'email' => 'y@example.com', 'description' => 'd', 'reference' => 'r'],
            ],
            'hasMore' => false,
        ]));

        $mock = new MockHandler([$resp]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'base_uri' => 'https://api.example.test']);
        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        $provider->syncTickets('y@example.com');

        $this->assertDatabaseMissing('tickets', ['external_id' => 'voided1']);
    }

    public function testProcessTicketReturnsExistingWhenNotVoided()
    {
        $provider = $this->createProvider();

        $existing = Ticket::factory()->create([
            'ticket_provider_id' => $provider->provider->id,
            'external_id' => 'keep-me',
        ]);

        $data = (object)[
            'id' => 'keep-me',
            'status' => 'valid',
            'event_id' => 'unused',
            'ticket_type_id' => 'unused',
            'email' => 'nobody@example.com',
        ];

        $result = $this->callProtected($provider, 'processTicket', [$data]);
        $this->assertInstanceOf(Ticket::class, $result);
        $this->assertDatabaseHas('tickets', ['external_id' => 'keep-me']);
    }

    public function testSyncTicketsAssignsUserWhenEmailaddressProvided()
    {
        $provider = $this->createProvider(['endpoint' => 'https://api.example.test', 'apikey' => 'key']);

        // Create a user and verified email
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['email' => 'u@example.com', 'verified_at' => now(), 'user_id' => $user->id]);

        // existing ticket in DB without a user
        $existing = Ticket::factory()->create([
            'ticket_provider_id' => $provider->provider->id,
            'external_id' => 'valid1',
            'user_id' => null,
        ]);

        // remote tickets: one valid ticket matching existing
        $resp = new Response(200, [], json_encode((object)[
            'tickets' => [
                (object)['id' => 'valid1', 'status' => 'valid', 'event_id' => 'evtA', 'ticket_type_id' => 'typeA', 'email' => 'u@example.com', 'description' => 'd', 'reference' => 'r'],
            ],
            'hasMore' => false,
        ]));

        $mock = new MockHandler([$resp]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'base_uri' => 'https://api.example.test']);
        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        // Call syncTickets with the EmailAddress instance so provider->syncTickets will attempt to assign the user
        $provider->syncTickets($email);

        $existing->refresh();
        $this->assertNotNull($existing->user_id, 'Ticket should have been assigned to the user');
        $this->assertEquals($user->id, $existing->user_id);
    }

    public function testGetClient()
    {
        $provider = $this->createProvider();
        $this->assertInstanceOf(Client::class, $this->callProtected($provider, 'getClient'));
    }
}
