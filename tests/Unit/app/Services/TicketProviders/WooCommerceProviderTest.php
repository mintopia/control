<?php

namespace Tests\Unit\app\Services\TicketProviders;

use App\Exceptions\TicketProviderWebhookException;
use App\Models\TicketProvider;
use App\Models\ProviderSetting;
use App\Models\TicketType;
use App\Models\Ticket;
use App\Models\User;
use App\Models\EmailAddress;
use App\Services\TicketProviders\WooCommerceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Tests\Unit\app\Services\TicketProviders\DummyWooCommerceProvider;
use GuzzleHttp\Client;

class WooCommerceProviderTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_config_mapping_returns_expected_array()
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

    public function test_verify_webhook_throws_if_no_secret()
    {
        $provider = $this->getProvider();
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['foo' => 'bar']));
        $this->expectException(TicketProviderWebhookException::class);
        $verifyWebhook = \Closure::bind(function ($request) {
            return $this->verifyWebhook($request);
        }, $provider, get_class($provider));
        $verifyWebhook($request);
    }

    public function test_verify_webhook_throws_if_no_signature()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['foo' => 'bar']));
        $verifyWebhook = \Closure::bind(function ($request) {
            return $this->verifyWebhook($request);
        }, $provider, get_class($provider));
        $this->expectException(TicketProviderWebhookException::class);
        $verifyWebhook($request);
    }

    public function test_verify_webhook_throws_if_hash_mismatch()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_WC_WEBHOOK_SIGNATURE' => base64_encode('invalid'),
        ], json_encode(['foo' => 'bar']));
        $verifyWebhook = \Closure::bind(function ($request) {
            return $this->verifyWebhook($request);
        }, $provider, get_class($provider));
        $this->expectException(TicketProviderWebhookException::class);
        $verifyWebhook($request);
    }

    public function test_verify_webhook_returns_true_on_valid_signature()
    {
        $secret = 'secret';
        $provider = $this->getProvider(['webhook_secret' => $secret]);
        $content = json_encode(['foo' => 'bar']);
        $hash = hash_hmac('sha256', $content, $secret, true);
        $signature = base64_encode($hash);
        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X_WC_WEBHOOK_SIGNATURE' => $signature,
        ], $content);
        $verifyWebhook = \Closure::bind(function ($request) {
            return $this->verifyWebhook($request);
        }, $provider, get_class($provider));
        $this->assertTrue($verifyWebhook($request));
    }

    public function test_process_webhook_returns_true()
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

        // Use DummyWooCommerceProvider to override protected behaviour
        $dummy = new \Tests\Unit\app\Services\TicketProviders\DummyWooCommerceProvider($providerModel);
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

    public function test_get_qr_code_returns_expected_url()
    {
        $provider = $this->getProvider();
        $data = (object)['id' => 'abc123'];
        $getQrCode = \Closure::bind(function ($data) {
            return $this->getQrCode($data);
        }, $provider, get_class($provider));
        $url = $getQrCode($data);
        $this->assertStringContainsString('abc123', $url);
        $this->assertStringStartsWith('https://api.qrserver.com/v1/create-qr-code/', $url);
    }

    public function test_parse_order_returns_tickets()
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
        $parseOrder = \Closure::bind(function ($order) {
            return $this->parseOrder($order);
        }, $provider, get_class($provider));
        $tickets = $parseOrder($order);
        $this->assertCount(2, $tickets);
        $this->assertArrayHasKey('1-10-1', $tickets);
        $this->assertArrayHasKey('1-10-2', $tickets);
        $this->assertEquals('valid', $tickets['1-10-1']->status);
    }

    public function test_get_events_returns_expected_array()
    {
        $provider = $this->getProvider();
        $provider->getProvider()->events = collect([
            (object)[
                'external_id' => 1,
                'event' => (object)['name' => 'Event 1'],
            ],
            (object)[
                'external_id' => 2,
                'event' => (object)['name' => 'Event 2'],
            ],
        ]);
        $events = $provider->getEvents();
        $this->assertArrayHasKey(1, $events);
        $this->assertArrayHasKey(2, $events);
        $this->assertContains('New Event', $events);
    }

    public function test_get_ticket_types_returns_cached_data()
    {
        $provider = $this->getProvider();
        $eventId = 'evt-1';
        $key = "ticketproviders.{$provider->getProvider()->id}.{$provider->getProvider()->cache_prefix}.events.{$eventId}.tickettypes";
        Cache::put($key, ['type1' => 'VIP'], 10);
        $types = $provider->getTicketTypes($eventId);
        $this->assertEquals(['type1' => 'VIP'], $types);
    }

    public function test_get_client()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyWooCommerceProvider($prov);
        $this->assertInstanceOf(Client::class, $dummy->getClientPublic());
    }

    public function test_get_events()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyWooCommerceProvider($prov);
        $this->assertIsArray($dummy->getEventsPublic());
    }

    public function test_get_type()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyWooCommerceProvider($prov);
        $this->assertInstanceOf(TicketType::class, $dummy->getTypePublic('type1'));
    }

    public function test_get_tickets()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyWooCommerceProvider($prov);
        $this->assertIsArray($dummy->getTicketsPublic());
    }

    public function test_get_ticket_types()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyWooCommerceProvider($prov);
        $this->assertIsArray($dummy->getTicketTypesPublic('evt-1'));
    }

    public function test_process_ticket()
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
        $dummy = new DummyWooCommerceProvider($prov);
        $this->assertNotNull($dummy->processTicketPublic($data));
    }

    public function test_make_ticket()
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
        $dummy = new DummyWooCommerceProvider($prov);
        $ticket = $dummy->makeTicketPublic(null, $data);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals('t1', $ticket->external_id);
    }

    public function test_get_tickets_fetches_from_api_and_pages()
    {
        $provider = $this->getProvider(['apikey' => 'key', 'endpoint' => 'https://api.example.test']);

        $order1 = (object)[
            'id' => 1,
            'status' => 'completed',
            'billing' => (object)['email' => 'a@x.com'],
            'line_items' => [(object)['id' => 10, 'product_id' => 100, 'name' => 'T', 'quantity' => 1]],
        ];
        $resp1 = new \GuzzleHttp\Psr7\Response(200, [], json_encode([$order1]));
        $resp2 = new \GuzzleHttp\Psr7\Response(200, [], json_encode([]));

        $mock = new \GuzzleHttp\Handler\MockHandler([$resp1, $resp2]);
        $handler = \GuzzleHttp\HandlerStack::create($mock);
        $client = new \GuzzleHttp\Client(['handler' => $handler]);

        $ref = new \ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        $getTickets = \Closure::bind(function ($address = null) {
            return $this->getTickets($address);
        }, $provider, get_class($provider));

        $tickets = $getTickets(null);
        $this->assertArrayHasKey('1-10-1', $tickets);
    }

    public function test_get_tickets_filters_by_address()
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

        $resp1 = new \GuzzleHttp\Psr7\Response(200, [], json_encode([$order1, $order2]));
        $resp2 = new \GuzzleHttp\Psr7\Response(200, [], json_encode([]));

        $mock = new \GuzzleHttp\Handler\MockHandler([$resp1, $resp2]);
        $handler = \GuzzleHttp\HandlerStack::create($mock);
        $client = new \GuzzleHttp\Client(['handler' => $handler]);

        $ref = new \ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        $getTickets = \Closure::bind(function ($address = null) {
            return $this->getTickets($address);
        }, $provider, get_class($provider));

        $tickets = $getTickets('match@example.test');
        $this->assertArrayHasKey('1-10-1', $tickets);
        $this->assertArrayNotHasKey('2-11-1', $tickets);
    }

    public function test_sync_tickets_deletes_voided_ticket()
    {
        $prov = $this->getProvider()->getProvider();

        $existing = Ticket::factory()->create(['ticket_provider_id' => $prov->id, 'external_id' => '1-10-1']);

        $mock = new class($prov) extends WooCommerceProvider {
            public function __construct(?\App\Models\TicketProvider $provider = null)
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

    public function test_sync_tickets_associates_user_when_emailaddress_passed()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();

        // Create a user and email address
        $user = User::factory()->create();
        $email = EmailAddress::factory()->create(['email' => 'user+wc@example.com', 'user_id' => $user->id, 'verified_at' => now()]);

        // Existing ticket without a user
        $ticket = Ticket::factory()->create(['ticket_provider_id' => $prov->id, 'external_id' => '1-10-1', 'user_id' => null]);

        // Create a provider subclass that returns the parsed ticket for the email
        $mock = new class($prov) extends WooCommerceProvider {
            public function __construct(?\App\Models\TicketProvider $provider = null)
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

    public function test_process_tickets_invoked_by_dummy()
    {
        $provider = $this->createProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyWooCommerceProvider($prov);
        $tickets = [(object)['id' => 'w1', 'ticket_type_id' => 'type1', 'event_id' => 'evt1', 'email' => 'a@b.test', 'description' => 'd']];
        $dummy->processTicketsPublic($tickets, 'a@b.test');
        $this->assertTrue($dummy->processCalled);
    }

    public function test_process_tickets_adds_missing_when_order_completed()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();

        // Ensure Event and TicketType mapping exist for provider
        $event = \App\Models\Event::factory()->create();
        \App\Models\EventMapping::factory()->for($event)->for($prov, 'provider')->create(['external_id' => 'evt1']);
        $type = \App\Models\TicketType::factory()->for($event)->create();
        \App\Models\TicketTypeMapping::create(['ticket_type_id' => $type->id, 'ticket_provider_id' => $prov->id, 'external_id' => 100]);

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
        $processTickets = \Closure::bind(function ($ticketData, $address, $user = null) {
            return $this->processTickets($ticketData, $address, $user);
        }, $provider, get_class($provider));

        $processTickets(['5-50-1' => $parsed], 'a@b.test', null);

        $this->assertDatabaseHas('tickets', ['external_id' => '5-50-1']);
    }

    public function test_get_ticket_types_fetches_from_api_and_caches()
    {
        $provider = $this->getProvider(['apikey' => 'key', 'endpoint' => 'https://api.example.test']);
        $prov = $provider->getProvider();
        $eventId = 'evt-1';
        $key = "ticketproviders.{$prov->id}.{$prov->cache_prefix}.events.{$eventId}.tickettypes";
        Cache::forget($key);

        $resp1 = new \GuzzleHttp\Psr7\Response(200, [], json_encode([(object)['id' => 100, 'name' => 'VIP']]));
        $resp2 = new \GuzzleHttp\Psr7\Response(200, [], json_encode([]));
        $mock = new \GuzzleHttp\Handler\MockHandler([$resp1, $resp2]);
        $handler = \GuzzleHttp\HandlerStack::create($mock);
        $client = new \GuzzleHttp\Client(['handler' => $handler]);

        $ref = new \ReflectionClass($provider);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($provider, $client);

        $types = $provider->getTicketTypes($eventId);
        $this->assertArrayHasKey(100, $types);
        $this->assertEquals($types, Cache::get($key));
    }

    public function test_make_ticket_returns_null_when_type_missing()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();

        $order = (object)['id' => 1, 'billing' => (object)['email' => 'a@b.com'], 'status' => 'completed'];
        $item = (object)['id' => 10, 'product_id' => 9999, 'name' => 'X'];
        $data = (object)['id' => '1-10-1', 'order' => $order, 'item' => $item, 'ticket_type_id' => 'no-type', 'event_id' => 'evtX', 'email' => 'a@b.com', 'description' => 'd'];

        $makeTicket = \Closure::bind(function ($user, $data) {
            return $this->makeTicket($user, $data);
        }, $provider, get_class($provider));
        $this->assertNull($makeTicket(null, $data));
    }

    public function test_make_ticket_uses_email_to_find_user()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyWooCommerceProvider($prov);

        $user = User::factory()->create();
        EmailAddress::factory()->create(['email' => 'email-user@example.com', 'verified_at' => now(), 'user_id' => $user->id]);

        $data = (object)['id' => '2-20-1', 'ticket_type_id' => 'typeY', 'event_id' => 'evtY', 'email' => 'email-user@example.com', 'description' => 'd'];
        $ticket = $dummy->makeTicketPublic(null, $data);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals($user->id, $ticket->user_id);
    }

    public function test_make_ticket_respects_supplied_user()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $dummy = new DummyWooCommerceProvider($prov);

        $userA = User::factory()->create();
        $userB = User::factory()->create();
        EmailAddress::factory()->create(['email' => 'userb@example.com', 'verified_at' => now(), 'user_id' => $userB->id]);

        $data = (object)['id' => '3-30-1', 'ticket_type_id' => 'typeZ', 'event_id' => 'evtZ', 'email' => 'userb@example.com', 'description' => 'd'];
        $ticket = $dummy->makeTicketPublic($userA, $data);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertEquals($userA->id, $ticket->user_id);
    }
}
