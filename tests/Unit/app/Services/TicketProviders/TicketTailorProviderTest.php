<?php

namespace Tests\Unit\app\Services\TicketProviders;

use App\Exceptions\TicketProviderWebhookException;
use App\Models\EmailAddress;
use App\Models\Event;
use App\Models\EventMapping;
use App\Models\ProviderSetting;
use App\Models\Ticket;
use App\Models\TicketProvider;
use App\Models\TicketType;
use App\Models\TicketTypeMapping;
use App\Models\User;
use App\Services\TicketProviders\TicketTailorProvider;
use Closure;
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

class TicketTailorProviderTest extends TestCase
{
    use RefreshDatabase;
    use ProviderTestHelpers;

    protected function getProvider(array $settings = [])
    {
        $ticketProvider = TicketProvider::factory()->create([
            'name' => 'Ticket Tailor',
            'code' => 'tickettailor',
            'provider_class' => TicketTailorProvider::class,
        ]);
        foreach ($settings as $code => $value) {
            ProviderSetting::factory()->create([
                'provider_id' => $ticketProvider->id,
                'provider_type' => TicketProvider::class,
                'code' => $code,
                'value' => $value,
            ]);
        }
        return new TicketTailorProvider($ticketProvider);
    }

    /**
     * Backwards-compatible alias used by some tests.
     */
    protected function createProvider(array $settings = [])
    {
        return $this->getProvider($settings);
    }

    public function testConfigMappingReturnsExpectedArray()
    {
        $provider = $this->getProvider();
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('apikey', $mapping);
        $this->assertEquals('API Key', $mapping['apikey']->name);
        $this->assertArrayHasKey('webhook_secret', $mapping);
        $this->assertEquals('Webhook Signing Secret', $mapping['webhook_secret']->name);
    }

    public function testVerifyWebhookReturnsTrueIfNoSecret()
    {
        $provider = $this->getProvider();
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['payload' => []]));
        $this->assertTrue($this->callProtected($provider, 'verifyWebhook', [$request]));
    }

    public function testVerifyWebhookThrowsIfHeaderMissing()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['payload' => []]));
        try {
            $this->callProtected($provider, 'verifyWebhook', [$request]);
            $this->fail('Expected TicketProviderWebhookException was not thrown');
        } catch (TicketProviderWebhookException $e) {
            $this->assertStringContainsString('Unable to retrieve', $e->getMessage());
        }
    }

    public function testVerifyWebhookThrowsIfSignatureInvalid()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $timestamp = now()->timestamp;
        $header = "t={$timestamp},v1=invalidsignature";
        $request = Request::create('/webhook', 'POST', [], [], [], ['HTTP_TICKETTAILOR_WEBHOOK_SIGNATURE' => $header], 'body');
        try {
            $this->callProtected($provider, 'verifyWebhook', [$request]);
            $this->fail('Expected TicketProviderWebhookException was not thrown');
        } catch (TicketProviderWebhookException $e) {
            $this->assertStringContainsString('Hash does not match', $e->getMessage());
        }
    }

    public function testVerifyWebhookThrowsIfTimestampTooOld()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $timestamp = now()->subMinutes(10)->timestamp;
        $body = 'body';
        $signature = hash_hmac('sha256', $timestamp . $body, 'secret');
        $header = "t={$timestamp},v1={$signature}";
        $request = Request::create('/webhook', 'POST', [], [], [], ['HTTP_TICKETTAILOR_WEBHOOK_SIGNATURE' => $header], $body);
        try {
            $this->callProtected($provider, 'verifyWebhook', [$request]);
            $this->fail('Expected TicketProviderWebhookException was not thrown');
        } catch (TicketProviderWebhookException $e) {
            $this->assertStringContainsString('more than 5 minutes', $e->getMessage());
        }
    }

    public function testVerifyWebhookReturnsTrueOnValidSignature()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $timestamp = now()->timestamp;
        $body = 'body';
        $signature = hash_hmac('sha256', $timestamp . $body, 'secret');
        $header = "t={$timestamp},v1={$signature}";
        $request = Request::create('/webhook', 'POST', [], [], [], ['HTTP_TICKETTAILOR_WEBHOOK_SIGNATURE' => $header], $body);
        $this->assertTrue($this->callProtected($provider, 'verifyWebhook', [$request]));
    }

    public function testProcessWebhookCallsVerifyAndProcessTicket()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $prov = $provider->getProvider();
        $timestamp = now()->timestamp;
        $body = json_encode(['payload' => (object)['id' => 'abc']]);
        $signature = hash_hmac('sha256', $timestamp . $body, 'secret');
        $header = "t={$timestamp},v1={$signature}";
        $request = Request::create('/webhook', 'POST', [], [], [], ['HTTP_tickettailor-webhook-signature' => $header], $body);

        // Create an anonymous subclass that overrides processTicket to record invocation
        $mock = new class ($prov) extends TicketTailorProvider {
            public bool $wasCalled = false;

            public function __construct(?TicketProvider $provider = null)
            {
                parent::__construct($provider);
            }

            protected function verifyWebhook(Request $request): bool
            {
                // trust the signature we created in the test
                return true;
            }

            protected function processTicket(object $data): ?Ticket
            {
                $this->wasCalled = true;
                return null;
            }
        };

        $result = $mock->processWebhook($request);
        $this->assertTrue($result);
        $this->assertTrue($mock->wasCalled, 'processTicket was not called');
    }

    public function testGetQrCodeReturnsExpectedUrl()
    {
        $provider = $this->getProvider();
        $data = (object)['barcode' => 'abc123'];
        $url = $this->callProtected($provider, 'getQrCode', [$data]);
        $this->assertStringContainsString('abc123', $url);
        $this->assertStringStartsWith('https://api.qrserver.com/v1/create-qr-code/', $url);
    }

    public function testDummyVerifyWebhookAndQrcodeViaHelper()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);

        // No secret configured -> verifyWebhook should return true
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['payload' => []]));
        $this->assertTrue($this->callProtected($dummy, 'verifyWebhook', [$request]));

        // getQrCode via helper
        $data = (object)['barcode' => 'zz'];
        $this->assertStringContainsString('zz', $this->callProtected($dummy, 'getQrCode', [$data]));
    }

    public function testMakeTicketReturnsNullWhenEventMissing()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);

        $data = (object)[
            'id' => 'x1',
            'event_id' => 'non-existent',
            'ticket_type_id' => 'no-type',
            'email' => 'noone@example.com',
            'barcode' => 'b',
            'description' => 'desc',
        ];
        $this->assertNull($this->callProtected($dummy, 'makeTicket', [null, $data]));
    }

    public function testGetEventsReturnsCachedData()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $key = "ticketproviders.{$prov->id}.{$prov->cache_prefix}.events";
        Cache::put($key, ['evt1' => 'Event 1'], 10);
        $events = $provider->getEvents();
        $this->assertEquals(['evt1' => 'Event 1'], $events);
    }

    public function testGetTicketTypesReturnsCachedData()
    {
        $provider = $this->getProvider();
        $eventId = 'evt-1';
        $prov = $provider->getProvider();
        $key = "ticketproviders.{$prov->id}.{$prov->cache_prefix}.events.{$eventId}.tickettypes";
        Cache::put($key, ['type1' => 'VIP'], 10);
        $types = $provider->getTicketTypes($eventId);
        $this->assertEquals(['type1' => 'VIP'], $types);
    }

    public function testSyncTicketsRemovesVoidedAndAddsMissing()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();

        // Existing ticket that should be removed (voided)
        $voided = Ticket::factory()->create([
            'ticket_provider_id' => $prov->id,
            'external_id' => 't2',
        ]);

        // Prepare ticket data returned from API
        $ticketValid = (object)[
            'id' => 't1',
            'status' => 'valid',
            'email' => 'new@example.com',
            'event_id' => 'evt-1',
            'ticket_type_id' => 'type-1',
            'barcode' => 'b1',
            'description' => 'Test Ticket',
        ];
        $ticketVoided = (object)[
            'id' => 't2',
            'status' => 'voided',
            'email' => 'old@example.com',
            'event_id' => 'evt-1',
            'ticket_type_id' => 'type-1',
            'barcode' => 'b2',
            'description' => 'Old Ticket',
        ];

        // Use an anonymous provider subclass to override protected methods instead of mocking them
        $mock = new class ($prov) extends TicketTailorProvider {
            public array $stubTickets = [];

            public function __construct(?TicketProvider $provider = null)
            {
                parent::__construct($provider);
            }

            protected function getTickets(?string $address = null): array
            {
                return $this->stubTickets;
            }

            protected function makeTicket(?User $user, object $data): ?Ticket
            {
                return Ticket::factory()->create([
                    'ticket_provider_id' => $this->provider->id,
                    'external_id' => $data->id,
                ]);
            }
        };
        $mock->stubTickets = [
            't1' => $ticketValid,
            't2' => $ticketVoided,
        ];

        // Run sync
        $mock->syncTickets('new@example.com');

        $this->assertDatabaseMissing('tickets', ['external_id' => 't2']);
        $this->assertDatabaseHas('tickets', ['external_id' => 't1']);
    }

    public function testSyncTicketsAssociatesUserWhenEmailaddressPassed()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();

        // Create user and email address
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create([
            'email' => 'user@example.com',
            'user_id' => $user->id,
            'verified_at' => now(),
        ]);

        // Existing ticket without a user
        $ticket = Ticket::factory()->create([
            'ticket_provider_id' => $prov->id,
            'external_id' => 't1',
            'user_id' => null,
        ]);

        $ticketData = (object)[
            'id' => 't1',
            'status' => 'valid',
            'email' => 'user@example.com',
            'event_id' => 'evt-1',
            'ticket_type_id' => 'type-1',
            'barcode' => 'b1',
            'description' => 'Test Ticket',
        ];

        // @var TicketTailorProvider $mock
        $mock = new class ($prov) extends TicketTailorProvider {
            public array $stubTickets = [];

            public function __construct(?TicketProvider $provider = null)
            {
                parent::__construct($provider);
            }

            protected function getTickets(?string $address = null): array
            {
                return $this->stubTickets;
            }
        };
        $mock->stubTickets = [
            't1' => $ticketData,
        ];

        $mock->syncTickets($email);

        $ticket->refresh();
        $this->assertEquals($user->id, $ticket->user_id);
    }

    public function testGetClient()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);
        $this->assertInstanceOf(Client::class, $this->callProtected($dummy, 'getClient'));
    }

    public function testGetType()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);
        $this->assertInstanceOf(TicketType::class, $this->callProtected($dummy, 'getType', ['type1']));
    }

    public function testGetEvents()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);
        $this->assertIsArray($this->callProtected($dummy, 'getEvents'));
    }

    public function testGetTickets()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);
        $this->assertIsArray($this->callProtected($dummy, 'getTickets'));
    }

    public function testGetTicketTypes()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);
        $this->assertIsArray($this->callProtected($dummy, 'getTicketTypes', ['evt-1']));
    }

    public function testProcessTicket()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);
        $data = (object)[
            'id' => 't1',
            'event_id' => 'evt1',
            'ticket_type_id' => 'type1',
            'email' => 'foo@example.com',
            'description' => 'Test ticket',
            'reference' => 'ref1',
        ];

        $this->assertNotNull($this->callProtected($dummy, 'processTicket', [$data]));
    }

    public function testMakeTicket()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);
        $data = (object)[
            'id' => 't1',
            'event_id' => 'evt1',
            'ticket_type_id' => 'type1',
            'email' => 'foo@example.com',
            'description' => 'Test ticket',
            'reference' => 'ref1',
        ];

        $ticket = $this->callProtected($dummy, 'makeTicket', [null, $data]);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals('t1', $ticket->external_id);
    }

    // --- Additional branch tests added below ---

    public function testGetTicketsFetchesFromApiAndPages()
    {
        $provider = $this->createProvider(['apikey' => 'key', 'endpoint' => 'https://api.example.test']);
        // prepare two paged responses
        $resp1 = new Response(200, [], json_encode((object)[
            'data' => [(object)['id' => '1', 'status' => 'valid', 'email' => 'a@x.com', 'event_id' => 'e1', 'ticket_type_id' => 't1', 'barcode' => 'b1', 'description' => 'd1']],
            'links' => (object)['next' => true],
        ]));
        $resp2 = new Response(200, [], json_encode((object)[
            'data' => [(object)['id' => '2', 'status' => 'valid', 'email' => 'b@x.com', 'event_id' => 'e2', 'ticket_type_id' => 't2', 'barcode' => 'b2', 'description' => 'd2']],
            'links' => (object)['next' => null],
        ]));

        $mock = new MockHandler([$resp1, $resp2]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'base_uri' => 'https://api.example.test']);

        // set client onto provider instance
        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        // call the protected getTickets via bound closure
        $tickets = $this->callProtected($provider, 'getTickets', [null]);
        $this->assertArrayHasKey('2', $tickets);
    }

    public function testGetTicketsWithAddressFetchesFromApiAndPages()
    {
        $provider = $this->createProvider(['apikey' => 'key', 'endpoint' => 'https://api.example.test']);
        // prepare two paged responses
        $resp1 = new Response(200, [], json_encode((object)[
            'data' => [(object)['id' => '1', 'status' => 'valid', 'email' => 'a@x.com', 'event_id' => 'e1', 'ticket_type_id' => 't1', 'barcode' => 'b1', 'description' => 'd1']],
            'links' => (object)['next' => true],
        ]));
        $resp2 = new Response(200, [], json_encode((object)[
            'data' => [(object)['id' => '2', 'status' => 'valid', 'email' => 'b@x.com', 'event_id' => 'e2', 'ticket_type_id' => 't2', 'barcode' => 'b2', 'description' => 'd2']],
            'links' => (object)['next' => null],
        ]));

        $mock = new MockHandler([$resp1, $resp2]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'base_uri' => 'https://api.example.test']);

        // set client onto provider instance
        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        // call the protected getTickets via bound closure with an address
        $tickets = $this->callProtected($provider, 'getTickets', ['filter@example.com']);
        $this->assertArrayHasKey('2', $tickets);
    }

    public function testGetEventsFetchesFromApiAndCaches()
    {
        $provider = $this->createProvider(['apikey' => 'key', 'endpoint' => 'https://api.example.test']);
        $key = "ticketproviders.{$provider->getProvider()->id}.{$provider->getProvider()->cache_prefix}.events";
        Cache::forget($key);

        $resp = new Response(200, [], json_encode((object)[
            'data' => [
                (object)['id' => 'evt1', 'name' => 'Event 1'],
                (object)['id' => 'evt2', 'name' => 'Event 2'],
            ],
            'links' => (object)['next' => null],
        ]));
        $mock = new MockHandler([$resp]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'base_uri' => 'https://api.example.test']);

        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        $events = $provider->getEvents();
        $this->assertEquals(['evt1' => 'Event 1', 'evt2' => 'Event 2'], $events);
        $this->assertEquals($events, Cache::get($key));
    }

    public function testGetTicketTypesFetchesFromApiAndCaches()
    {
        $provider = $this->createProvider(['apikey' => 'key', 'endpoint' => 'https://api.example.test']);
        $prov = $provider->getProvider();
        $eventId = 'evt-1';
        $key = "ticketproviders.{$prov->id}.{$prov->cache_prefix}.events.{$eventId}.tickettypes";
        Cache::forget($key);

        $resp = new Response(200, [], json_encode((object)[
            'ticket_types' => [
                (object)['id' => 'type1', 'name' => 'VIP'],
                (object)['id' => 'type2', 'name' => 'Standard'],
            ],
        ]));
        $mock = new MockHandler([$resp]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'base_uri' => 'https://api.example.test']);

        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        $types = $provider->getTicketTypes($eventId);
        $this->assertEquals(['type1' => 'VIP', 'type2' => 'Standard'], $types);
        $this->assertEquals($types, Cache::get($key));
    }

    public function testProcessTicketDeletesExistingWhenVoided()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);

        $existing = Ticket::factory()->create([
            'ticket_provider_id' => $prov->id,
            'external_id' => 'del-tt',
        ]);

        $data = (object)[
            'id' => 'del-tt',
            'status' => 'voided',
            'event_id' => 'evtX',
            'ticket_type_id' => 'typeX',
            'email' => 'noone@example.com',
            'barcode' => 'b',
            'description' => 'd',
        ];

        $this->callProtected($dummy, 'processTicket', [$data]);
        $this->assertDatabaseMissing('tickets', ['external_id' => 'del-tt']);
    }

    public function testProcessTicketReturnsNullWhenEventMissing()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);

        $data = (object)[
            'id' => 'px1',
            'status' => 'valid',
            'event_id' => 'non-existent-event',
            'ticket_type_id' => 'no-type',
            'email' => 'noone@example.com',
            'barcode' => 'b',
            'description' => 'desc',
        ];

        $this->assertNull($this->callProtected($dummy, 'processTicket', [$data]));
    }

    public function testProcessTicketLinksUserWhenEmailExists()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);

        $user = User::factory()->create();
        EmailAddress::factory()->create(['email' => 'u@example.com', 'verified_at' => now(), 'user_id' => $user->id]);

        $event = Event::factory()->create();
        EventMapping::factory()->for($event)->for($prov, 'provider')->create(['external_id' => 'evt1']);
        $type = TicketType::factory()->create();
        TicketTypeMapping::create(['ticket_type_id' => $type->id, 'ticket_provider_id' => $prov->id, 'external_id' => 'type1']);

        $data = (object)[
            'id' => 'tlink',
            'status' => 'valid',
            'event_id' => 'evt1',
            'ticket_type_id' => 'type1',
            'email' => 'u@example.com',
            'barcode' => 'b',
            'description' => 'd',
        ];

        $ticket = $this->callProtected($dummy, 'processTicket', [$data]);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals($user->id, $ticket->user_id);
    }

    public function testMakeTicketReturnsNullWhenTypeMissing()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();

        $event = Event::factory()->create();
        EventMapping::factory()->for($event)->for($prov, 'provider')->create(['external_id' => 'evtX']);

        $data = (object)[
            'id' => 'm1',
            'event_id' => 'evtX',
            'ticket_type_id' => 'no-such-type',
            'email' => 'a@b.com',
            'barcode' => 'b',
            'description' => 'd',
        ];

        // call protected makeTicket on real provider so Dummy's auto-creation isn't used
        $this->assertNull($this->callProtected($provider, 'makeTicket', [null, $data]));
    }

    public function testMakeTicketUsesEmailToFindUser()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);

        $user = User::factory()->create();
        EmailAddress::factory()->create(['email' => 'email-user@example.com', 'verified_at' => now(), 'user_id' => $user->id]);

        $event = Event::factory()->create();
        EventMapping::factory()->for($event)->for($prov, 'provider')->create(['external_id' => 'evtY']);
        $type = TicketType::factory()->create();
        TicketTypeMapping::create(['ticket_type_id' => $type->id, 'ticket_provider_id' => $prov->id, 'external_id' => 'typeY']);

        $data = (object)[
            'id' => 'm2',
            'event_id' => 'evtY',
            'ticket_type_id' => 'typeY',
            'email' => 'email-user@example.com',
            'barcode' => 'b',
            'description' => 'd',
        ];

        $ticket = $this->callProtected($dummy, 'makeTicket', [null, $data]);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals($user->id, $ticket->user_id);
    }

    public function testMakeTicketRespectsSuppliedUser()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeTicketTailorProvider($prov);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        EmailAddress::factory()->create(['email' => 'userb@example.com', 'verified_at' => now(), 'user_id' => $userB->id]);

        $event = Event::factory()->create();
        EventMapping::factory()->for($event)->for($prov, 'provider')->create(['external_id' => 'evtZ']);
        $type = TicketType::factory()->create();
        TicketTypeMapping::create(['ticket_type_id' => $type->id, 'ticket_provider_id' => $prov->id, 'external_id' => 'typeZ']);

        $data = (object)[
            'id' => 'm3',
            'event_id' => 'evtZ',
            'ticket_type_id' => 'typeZ',
            'email' => 'userb@example.com',
            'barcode' => 'b',
            'description' => 'd',
        ];

        $ticket = $this->callProtected($dummy, 'makeTicket', [$userA, $data]);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals($userA->id, $ticket->user_id);
    }
}
