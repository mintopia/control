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
use App\Services\TicketProviders\WooCommerceProvider;
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

class WooCommerceProviderTest extends TestCase
{
    use RefreshDatabase;
    use ProviderTestHelpers;

    protected function getProvider(array $settings = [])
    {
        $ticketProvider = TicketProvider::factory()->create([
            'name' => 'Woo Commerce',
            'code' => 'woocommerce',
            'provider_class' => WooCommerceProvider::class,
        ]);
        foreach ($settings as $code => $value) {
            ProviderSetting::factory()->create([
                'provider_id' => $ticketProvider->id,
                'provider_type' => TicketProvider::class,
                'code' => $code,
                'value' => $value,
            ]);
        }
        return new WooCommerceProvider($ticketProvider);
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

        $this->assertArrayHasKey('endpoint', $mapping);
        $this->assertEquals('API Endpoint', $mapping['endpoint']->name);
        $this->assertArrayHasKey('apikey', $mapping);
        $this->assertEquals('Consumer Key', $mapping['apikey']->name);
        $this->assertArrayHasKey('apisecret', $mapping);
        $this->assertEquals('Consumer Secret', $mapping['apisecret']->name);
        $this->assertArrayHasKey('webhook_secret', $mapping);
        $this->assertEquals('Webhook Secret', $mapping['webhook_secret']->name);
    }

    public function testVerifyWebhookThrowsIfNoSecret()
    {
        $provider = $this->getProvider();
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['foo' => 'bar']));
        $this->expectException(TicketProviderWebhookException::class);
        $this->callProtected($provider, 'verifyWebhook', [$request]);
    }

    public function testVerifyWebhookThrowsIfNoSignature()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['foo' => 'bar']));
        $this->expectException(TicketProviderWebhookException::class);
        $this->callProtected($provider, 'verifyWebhook', [$request]);
    }

    public function testVerifyWebhookThrowsIfHashMismatch()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_WC_WEBHOOK_SIGNATURE' => base64_encode('invalid'),
        ], json_encode(['foo' => 'bar']));
        $this->expectException(TicketProviderWebhookException::class);
        $this->callProtected($provider, 'verifyWebhook', [$request]);
    }

    public function testVerifyWebhookReturnsTrueOnValidSignature()
    {
        $secret = 'secret';
        $provider = $this->getProvider(['webhook_secret' => $secret]);
        $content = json_encode(['foo' => 'bar']);
        $hash = hash_hmac('sha256', $content, $secret, true);
        $signature = base64_encode($hash);
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_WC_WEBHOOK_SIGNATURE' => $signature,
        ], $content);
        $this->assertTrue($this->callProtected($provider, 'verifyWebhook', [$request]));
    }

    public function testProcessWebhookReturnsTrue()
    {
        $secret = 'secret';
        $provider = $this->getProvider(['webhook_secret' => $secret]);
        $order = (object)[
            'id' => 1,
            'status' => 'completed',
            'billing' => (object)['email' => 'test@example.com'],
            'line_items' => [
                (object)[
                    'id' => 10,
                    'product_id' => 100,
                    'name' => 'Test Ticket',
                    'quantity' => 1,
                ]
            ]
        ];
        $content = json_encode($order);
        $hash = hash_hmac('sha256', $content, $secret, true);
        $signature = base64_encode($hash);
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_WC_WEBHOOK_SIGNATURE' => $signature,
        ], $content);

        // Use the real TicketProvider Eloquent model (created by getProvider())
        $providerModel = $provider->getProvider();
        // ensure the model is fresh from the DB so settings() queries work
        $providerModel->refresh();
        // sanity check that the secret is available from the DB
        $this->assertEquals($secret, $providerModel->getSetting('webhook_secret'));

        // Use test factory to override protected behaviour
        $dummy = $this->makeWooCommerceProvider($providerModel);
        $dummy->forceVerify = true;
        $dummy->parseOverride = [
            (object)[
                'id' => '1-10-1',
                'ticket_type_id' => 100,
                'order' => $order,
                'item' => $order->line_items[0],
                'status' => 'valid',
                'email' => 'test@example.com',
            ]
        ];

        $this->assertSame($providerModel, $dummy->getProvider());
        $this->assertTrue($dummy->processWebhook($request));
        $this->assertTrue($dummy->processCalled);
    }

    public function testGetQrCodeReturnsExpectedUrl()
    {
        $provider = $this->getProvider();
        $data = (object)['id' => 'abc123'];
        $url = $this->callProtected($provider, 'getQrCode', [$data]);
        $this->assertStringContainsString('abc123', $url);
        $this->assertStringStartsWith('https://api.qrserver.com/v1/create-qr-code/', $url);
    }

    public function testParseOrderReturnsTickets()
    {
        $provider = $this->getProvider();
        $order = (object)[
            'id' => 1,
            'status' => 'completed',
            'billing' => (object)['email' => 'test@example.com'],
            'line_items' => [
                (object)[
                    'id' => 10,
                    'product_id' => 100,
                    'name' => 'Test Ticket',
                    'quantity' => 2,
                ]
            ]
        ];
        $tickets = $this->callProtected($provider, 'parseOrder', [$order]);
        $this->assertCount(2, $tickets);
        $this->assertArrayHasKey('1-10-1', $tickets);
        $this->assertArrayHasKey('1-10-2', $tickets);
        $this->assertEquals('valid', $tickets['1-10-1']->status);
    }

    public function testGetEventsReturnsExpectedArray()
    {
        $provider = $this->getProvider();
        $provider->getProvider()->setRelation('events', collect([
            (object)[
                'external_id' => 1,
                'event' => (object)['name' => 'Event 1'],
            ],
            (object)[
                'external_id' => 2,
                'event' => (object)['name' => 'Event 2'],
            ],
        ]));
        $events = $provider->getEvents();
        $this->assertArrayHasKey(1, $events);
        $this->assertArrayHasKey(2, $events);
        $this->assertContains('New Event', $events);
    }

    public function testGetTicketTypesReturnsCachedData()
    {
        $provider = $this->getProvider();
        $eventId = 'evt-1';
        $key = "ticketproviders.{$provider->getProvider()->id}.{$provider->getProvider()->cache_prefix}.events.{$eventId}.tickettypes";
        Cache::put($key, ['type1' => 'VIP'], 10);
        $types = $provider->getTicketTypes($eventId);
        $this->assertEquals(['type1' => 'VIP'], $types);
    }

    public function testGetClient()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeWooCommerceProvider($prov);
        $this->assertInstanceOf(Client::class, $this->callProtected($dummy, 'getClient'));
    }

    public function testGetEvents()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeWooCommerceProvider($prov);
        $this->assertIsArray($this->callProtected($dummy, 'getEvents'));
    }

    public function testGetType()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeWooCommerceProvider($prov);
        $this->assertInstanceOf(TicketType::class, $this->callProtected($dummy, 'getType', ['type1']));
    }

    public function testGetTickets()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeWooCommerceProvider($prov);
        $this->assertIsArray($this->callProtected($dummy, 'getTickets'));
    }

    public function testGetTicketTypes()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeWooCommerceProvider($prov);
        $this->assertIsArray($this->callProtected($dummy, 'getTicketTypes', ['evt-1']));
    }

    public function testProcessTicket()
    {
        $provider = $this->createProvider();
        $data = (object)[
            'id' => 't1',
            'event_id' => 'evt1',
            'ticket_type_id' => 'type1',
            'email' => 'foo@example.com',
            'description' => 'Test ticket',
            'reference' => 'ref1',
        ];

        $prov = $provider->getProvider();
        $dummy = $this->makeWooCommerceProvider($prov);
        $this->assertNotNull($this->callProtected($dummy, 'processTicket', [$data]));
    }

    public function testMakeTicket()
    {
        $provider = $this->createProvider();
        $data = (object)[
            'id' => 't1',
            'event_id' => 'evt1',
            'ticket_type_id' => 'type1',
            'email' => 'foo@example.com',
            'description' => 'Test ticket',
            'reference' => 'ref1',
        ];

        $prov = $provider->getProvider();
        $dummy = $this->makeWooCommerceProvider($prov);
        $ticket = $this->callProtected($dummy, 'makeTicket', [null, $data]);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals('t1', $ticket->external_id);
    }

    public function testGetTicketsFetchesFromApiAndPages()
    {
        $provider = $this->getProvider(['apikey' => 'key', 'endpoint' => 'https://api.example.test']);

        $order1 = (object)[
            'id' => 1,
            'status' => 'completed',
            'billing' => (object)['email' => 'a@x.com'],
            'line_items' => [(object)['id' => 10, 'product_id' => 100, 'name' => 'T', 'quantity' => 1]],
        ];
        $resp1 = new Response(200, [], json_encode([$order1]));
        $resp2 = new Response(200, [], json_encode([]));

        $mock = new MockHandler([$resp1, $resp2]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'base_uri' => 'https://api.example.test']);

        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        $tickets = $this->callProtected($provider, 'getTickets', [null]);
        $this->assertArrayHasKey('1-10-1', $tickets);
    }

    public function testGetTicketsFiltersByAddress()
    {
        $provider = $this->getProvider(['apikey' => 'key', 'endpoint' => 'https://api.example.test']);

        $order1 = (object)[
            'id' => 1,
            'status' => 'completed',
            'billing' => (object)['email' => 'match@example.test'],
            'line_items' => [(object)['id' => 10, 'product_id' => 100, 'name' => 'T', 'quantity' => 1]],
        ];
        $order2 = (object)[
            'id' => 2,
            'status' => 'completed',
            'billing' => (object)['email' => 'other@example.test'],
            'line_items' => [(object)['id' => 11, 'product_id' => 101, 'name' => 'T2', 'quantity' => 1]],
        ];

        $resp1 = new Response(200, [], json_encode([$order1, $order2]));
        $resp2 = new Response(200, [], json_encode([]));

        $mock = new MockHandler([$resp1, $resp2]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'base_uri' => 'https://api.example.test']);

        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        $tickets = $this->callProtected($provider, 'getTickets', ['match@example.test']);
        $this->assertArrayHasKey('1-10-1', $tickets);
        $this->assertArrayNotHasKey('2-11-1', $tickets);
    }

    public function testSyncTicketsDeletesVoidedTicket()
    {
        $prov = $this->getProvider()->getProvider();

        $existing = Ticket::factory()->create(['ticket_provider_id' => $prov->id, 'external_id' => '1-10-1']);

        $mock = new class ($prov) extends WooCommerceProvider {
            public function __construct(?TicketProvider $provider = null)
            {
                parent::__construct($provider);
            }

            protected function getTickets(?string $address = null): array
            {
                // order->status not in ['processing','completed'] => treated as voided
                return [(object)['id' => '1-10-1', 'order' => (object)['billing' => (object)['email' => $address ?? 'a@b.test'], 'id' => 1, 'status' => 'cancelled'], 'item' => (object)['id' => 10, 'product_id' => 100, 'name' => 'T', 'quantity' => 1], 'status' => 'voided', 'email' => $address ?? 'a@b.test']];
            }
        };
        $mock->syncTickets('a@b.test');
        $this->assertDatabaseMissing('tickets', ['external_id' => '1-10-1']);
    }

    public function testSyncTicketsAssociatesUserWhenEmailaddressPassed()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();

        // Create a user and email address
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['email' => 'user+wc@example.com', 'user_id' => $user->id, 'verified_at' => now()]);

        // Existing ticket without a user
        $ticket = Ticket::factory()->create(['ticket_provider_id' => $prov->id, 'external_id' => '1-10-1', 'user_id' => null]);

        // Create a provider subclass that returns the parsed ticket for the email
        $mock = new class ($prov) extends WooCommerceProvider {
            public function __construct(?TicketProvider $provider = null)
            {
                parent::__construct($provider);
            }

            protected function getTickets(?string $address = null): array
            {
                return [(object)['id' => '1-10-1', 'order' => (object)['billing' => (object)['email' => $address], 'id' => 1, 'status' => 'completed'], 'item' => (object)['id' => 10, 'product_id' => 100, 'name' => 'T', 'quantity' => 1], 'status' => 'valid', 'email' => $address]];
            }
        };

        // Call syncTickets with EmailAddress model
        $mock->syncTickets($email);

        $ticket->refresh();
        $this->assertEquals($user->id, $ticket->user_id);
    }

    public function testProcessTicketsInvokedByDummy()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeWooCommerceProvider($prov);
        $tickets = [(object)['id' => 'w1', 'ticket_type_id' => 'type1', 'event_id' => 'evt1', 'email' => 'a@b.test', 'description' => 'd']];
        $this->callProtected($dummy, 'processTickets', [$tickets, 'a@b.test']);
        $this->assertTrue($dummy->processCalled);
    }

    public function testProcessTicketsAddsMissingWhenOrderCompleted()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();

        // Ensure Event and TicketType mapping exist for provider
        $event = Event::factory()->create();
        EventMapping::factory()->for($event)->for($prov, 'provider')->create(['external_id' => 'evt1']);
        $type = TicketType::factory()->for($event)->create();
        TicketTypeMapping::create(['ticket_type_id' => $type->id, 'ticket_provider_id' => $prov->id, 'external_id' => 100]);

        // Prepare a parsed ticket (order status completed -> valid)
        $parsed = (object)[
            'id' => '5-50-1',
            'ticket_type_id' => 100,
            'order' => (object)['billing' => (object)['email' => 'a@b.test'], 'id' => 5, 'status' => 'completed'],
            'item' => (object)['id' => 50, 'product_id' => 100, 'name' => 'T', 'quantity' => 1],
            'status' => 'valid',
            'email' => 'a@b.test',
        ];

        // Call protected processTickets on real provider via bound closure so we exercise parent logic
        $this->callProtected($provider, 'processTickets', [['5-50-1' => $parsed], 'a@b.test', null]);

        $this->assertDatabaseHas('tickets', ['external_id' => '5-50-1']);
    }

    public function testGetTicketTypesFetchesFromApiAndCaches()
    {
        $provider = $this->getProvider(['apikey' => 'key', 'endpoint' => 'https://api.example.test']);
        $prov = $provider->getProvider();
        $eventId = 'evt-1';
        $key = "ticketproviders.{$prov->id}.{$prov->cache_prefix}.events.{$eventId}.tickettypes";
        Cache::forget($key);

        $resp1 = new Response(200, [], json_encode([(object)['id' => 100, 'name' => 'VIP']]));
        $resp2 = new Response(200, [], json_encode([]));
        $mock = new MockHandler([$resp1, $resp2]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'base_uri' => 'https://api.example.test']);

        $ref = new ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        $types = $provider->getTicketTypes($eventId);
        $this->assertArrayHasKey(100, $types);
        $this->assertEquals($types, Cache::get($key));
    }

    public function testMakeTicketReturnsNullWhenTypeMissing()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();

        $order = (object)['id' => 1, 'billing' => (object)['email' => 'a@b.com'], 'status' => 'completed'];
        $item = (object)['id' => 10, 'product_id' => 9999, 'name' => 'X'];
        $data = (object)['id' => '1-10-1', 'order' => $order, 'item' => $item, 'ticket_type_id' => 'no-type', 'event_id' => 'evtX', 'email' => 'a@b.com', 'description' => 'd'];

        $this->assertNull($this->callProtected($provider, 'makeTicket', [null, $data]));
    }

    public function testMakeTicketUsesEmailToFindUser()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeWooCommerceProvider($prov);

        $user = User::factory()->create();
        EmailAddress::factory()->create(['email' => 'email-user@example.com', 'verified_at' => now(), 'user_id' => $user->id]);

        $data = (object)['id' => '2-20-1', 'ticket_type_id' => 'typeY', 'event_id' => 'evtY', 'email' => 'email-user@example.com', 'description' => 'd'];
        $ticket = $this->callProtected($dummy, 'makeTicket', [null, $data]);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals($user->id, $ticket->user_id);
    }

    public function testMakeTicketRespectsSuppliedUser()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = $this->makeWooCommerceProvider($prov);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        EmailAddress::factory()->create(['email' => 'userb@example.com', 'verified_at' => now(), 'user_id' => $userB->id]);

        $data = (object)['id' => '3-30-1', 'ticket_type_id' => 'typeZ', 'event_id' => 'evtZ', 'email' => 'userb@example.com', 'description' => 'd'];
        $ticket = $this->callProtected($dummy, 'makeTicket', [$userA, $data]);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals($userA->id, $ticket->user_id);
    }
}
