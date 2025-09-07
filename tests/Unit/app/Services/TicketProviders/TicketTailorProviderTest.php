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

class TicketTailorProviderTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_config_mapping_returns_expected_array()
    {
        $provider = $this->getProvider();
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('apikey', $mapping);
        $this->assertEquals('API Key', $mapping['apikey']->name);
        $this->assertArrayHasKey('webhook_secret', $mapping);
        $this->assertEquals('Webhook Signing Secret', $mapping['webhook_secret']->name);
    }

    public function test_verify_webhook_returns_true_if_no_secret()
    {
        $provider = $this->getProvider();
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['payload' => []]));
        $verifyWebhook = Closure::bind(function ($request) {
            return $this->verifyWebhook($request);
        }, $provider, get_class($provider));
        $this->assertTrue($verifyWebhook($request));
    }

    public function test_verify_webhook_throws_if_header_missing()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['payload' => []]));
        $verifyWebhook = Closure::bind(function ($request) {
            return $this->verifyWebhook($request);
        }, $provider, get_class($provider));
        try {
            $verifyWebhook($request);
            $this->fail('Expected TicketProviderWebhookException was not thrown');
        } catch (TicketProviderWebhookException $e) {
            $this->assertStringContainsString('Unable to retrieve', $e->getMessage());
        }
    }

    public function test_verify_webhook_throws_if_signature_invalid()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $timestamp = now()->timestamp;
        $header = "t={$timestamp},v1=invalidsignature";
        $request = Request::create('/webhook', 'POST', [], [], [], ['HTTP_tickettailor-webhook-signature' => $header], 'body');
        $verifyWebhook = Closure::bind(function ($request) {
            return $this->verifyWebhook($request);
        }, $provider, get_class($provider));
        try {
            $verifyWebhook($request);
            $this->fail('Expected TicketProviderWebhookException was not thrown');
        } catch (TicketProviderWebhookException $e) {
            $this->assertStringContainsString('Hash does not match', $e->getMessage());
        }
    }

    public function test_verify_webhook_throws_if_timestamp_too_old()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $timestamp = now()->subMinutes(10)->timestamp;
        $body = 'body';
        $signature = hash_hmac('sha256', $timestamp . $body, 'secret');
        $header = "t={$timestamp},v1={$signature}";
        $request = Request::create('/webhook', 'POST', [], [], [], ['HTTP_tickettailor-webhook-signature' => $header], $body);
        $verifyWebhook = Closure::bind(function ($request) {
            return $this->verifyWebhook($request);
        }, $provider, get_class($provider));
        try {
            $verifyWebhook($request);
            $this->fail('Expected TicketProviderWebhookException was not thrown');
        } catch (TicketProviderWebhookException $e) {
            $this->assertStringContainsString('more than 5 minutes', $e->getMessage());
        }
    }

    public function test_verify_webhook_returns_true_on_valid_signature()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $timestamp = now()->timestamp;
        $body = 'body';
        $signature = hash_hmac('sha256', $timestamp . $body, 'secret');
        $header = "t={$timestamp},v1={$signature}";
        $request = Request::create('/webhook', 'POST', [], [], [], ['HTTP_tickettailor-webhook-signature' => $header], $body);
        $verifyWebhook = Closure::bind(function ($request) {
            return $this->verifyWebhook($request);
        }, $provider, get_class($provider));
        $this->assertTrue($verifyWebhook($request));
    }

    public function test_process_webhook_calls_verify_and_process_ticket()
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

    public function test_get_qr_code_returns_expected_url()
    {
        $provider = $this->getProvider();
        $data = (object)['barcode' => 'abc123'];
        $getQrCode = Closure::bind(function ($data) {
            return $this->getQrCode($data);
        }, $provider, get_class($provider));
        $url = $getQrCode($data);
        $this->assertStringContainsString('abc123', $url);
        $this->assertStringStartsWith('https://api.qrserver.com/v1/create-qr-code/', $url);
    }

    public function test_dummy_verify_webhook_and_qrcode_via_helper()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);

        // No secret configured -> verifyWebhook should return true
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['payload' => []]));
        $this->assertTrue($dummy->verifyWebhookPublic($request));

        // getQrCode via helper
        $data = (object)['barcode' => 'zz'];
        $this->assertStringContainsString('zz', $dummy->getQrCodePublic($data));
    }

    public function test_make_ticket_returns_null_when_event_missing()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);

        $data = (object)[
            'id' => 'x1',
            'event_id' => 'non-existent',
            'ticket_type_id' => 'no-type',
            'email' => 'noone@example.com',
            'barcode' => 'b',
            'description' => 'desc',
        ];
        $this->assertNull($dummy->makeTicketPublic(null, $data));
    }

    public function test_get_events_returns_cached_data()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $key = "ticketproviders.{$prov->id}.{$prov->cache_prefix}.events";
        Cache::put($key, ['evt1' => 'Event 1'], 10);
        $events = $provider->getEvents();
        $this->assertEquals(['evt1' => 'Event 1'], $events);
    }

    public function test_get_ticket_types_returns_cached_data()
    {
        $provider = $this->getProvider();
        $eventId = 'evt-1';
        $prov = $provider->getProvider();
        $key = "ticketproviders.{$prov->id}.{$prov->cache_prefix}.events.{$eventId}.tickettypes";
        Cache::put($key, ['type1' => 'VIP'], 10);
        $types = $provider->getTicketTypes($eventId);
        $this->assertEquals(['type1' => 'VIP'], $types);
    }

    public function test_sync_tickets_removes_voided_and_adds_missing()
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

    public function test_sync_tickets_associates_user_when_emailaddress_passed()
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

    public function test_get_client()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);
        $this->assertInstanceOf(Client::class, $dummy->getClientPublic());
    }

    public function test_get_type()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);
        $this->assertInstanceOf(TicketType::class, $dummy->getTypePublic('type1'));
    }

    public function test_get_events()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);
        $this->assertIsArray($dummy->getEventsPublic());
    }

    public function test_get_tickets()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);
        $this->assertIsArray($dummy->getTicketsPublic());
    }

    public function test_get_ticket_types()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);
        $this->assertIsArray($dummy->getTicketTypesPublic('evt-1'));
    }

    public function test_process_ticket()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);
        $data = (object)[
            'id' => 't1',
            'event_id' => 'evt1',
            'ticket_type_id' => 'type1',
            'email' => 'foo@example.com',
            'description' => 'Test ticket',
            'reference' => 'ref1',
        ];

        $this->assertNotNull($dummy->processTicketPublic($data));
    }

    public function test_make_ticket()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);
        $data = (object)[
            'id' => 't1',
            'event_id' => 'evt1',
            'ticket_type_id' => 'type1',
            'email' => 'foo@example.com',
            'description' => 'Test ticket',
            'reference' => 'ref1',
        ];

        $ticket = $dummy->makeTicketPublic(null, $data);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals('t1', $ticket->external_id);
    }

    // --- Additional branch tests added below ---

    public function test_get_tickets_fetches_from_api_and_pages()
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
        $client = new Client(['handler' => $handler]);

        // set client onto provider instance
        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        // call the protected getTickets via bound closure
        $getTickets = Closure::bind(function ($address = null) {
            return $this->getTickets($address);
        }, $provider, get_class($provider));

        $tickets = $getTickets(null);
        $this->assertArrayHasKey('2', $tickets);
    }

    public function test_get_tickets_with_address_fetches_from_api_and_pages()
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
        $client = new Client(['handler' => $handler]);

        // set client onto provider instance
        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        // call the protected getTickets via bound closure with an address
        $getTickets = Closure::bind(function ($address = null) {
            return $this->getTickets($address);
        }, $provider, get_class($provider));

        $tickets = $getTickets('filter@example.com');
        $this->assertArrayHasKey('2', $tickets);
    }

    public function test_get_events_fetches_from_api_and_caches()
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
        $client = new Client(['handler' => $handler]);

        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        $events = $provider->getEvents();
        $this->assertEquals(['evt1' => 'Event 1', 'evt2' => 'Event 2'], $events);
        $this->assertEquals($events, Cache::get($key));
    }

    public function test_get_ticket_types_fetches_from_api_and_caches()
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
        $client = new Client(['handler' => $handler]);

        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        $types = $provider->getTicketTypes($eventId);
        $this->assertEquals(['type1' => 'VIP', 'type2' => 'Standard'], $types);
        $this->assertEquals($types, Cache::get($key));
    }

    public function test_process_ticket_deletes_existing_when_voided()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);

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

        $dummy->processTicketPublic($data);
        $this->assertDatabaseMissing('tickets', ['external_id' => 'del-tt']);
    }

    public function test_process_ticket_returns_null_when_event_missing()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);

        $data = (object)[
            'id' => 'px1',
            'status' => 'valid',
            'event_id' => 'non-existent-event',
            'ticket_type_id' => 'no-type',
            'email' => 'noone@example.com',
            'barcode' => 'b',
            'description' => 'desc',
        ];

        $this->assertNull($dummy->processTicketPublic($data));
    }

    public function test_process_ticket_links_user_when_email_exists()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);

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

        $ticket = $dummy->processTicketPublic($data);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals($user->id, $ticket->user_id);
    }

    public function test_make_ticket_returns_null_when_type_missing()
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
        $makeTicket = Closure::bind(function ($user, $data) {
            return $this->makeTicket($user, $data);
        }, $provider, get_class($provider));

        $this->assertNull($makeTicket(null, $data));
    }

    public function test_make_ticket_uses_email_to_find_user()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);

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

        $ticket = $dummy->makeTicketPublic(null, $data);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals($user->id, $ticket->user_id);
    }

    public function test_make_ticket_respects_supplied_user()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyTicketTailorProvider($prov);

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

        $ticket = $dummy->makeTicketPublic($userA, $data);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals($userA->id, $ticket->user_id);
    }
}
