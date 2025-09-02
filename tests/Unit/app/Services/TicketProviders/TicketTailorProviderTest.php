<?php

namespace Tests\Unit\app\Services\TicketProviders;

use App\Exceptions\TicketProviderWebhookException;
use App\Models\TicketProvider;
use App\Models\ProviderSetting;
use App\Models\TicketType;
use App\Models\Ticket;
use App\Models\User;
use App\Models\EmailAddress;
use App\Models\Event;
use App\Services\TicketProviders\TicketTailorProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Tests\Unit\app\Services\TicketProviders\DummyTicketTailorProvider;

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
        $verifyWebhook = \Closure::bind(function ($request) {
            return $this->verifyWebhook($request);
        }, $provider, get_class($provider));
        $this->assertTrue($verifyWebhook($request));
    }

    public function test_verify_webhook_throws_if_header_missing()
    {
        $provider = $this->getProvider(['webhook_secret' => 'secret']);
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['payload' => []]));
        $verifyWebhook = \Closure::bind(function ($request) {
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
        $verifyWebhook = \Closure::bind(function ($request) {
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
        $verifyWebhook = \Closure::bind(function ($request) {
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
        $verifyWebhook = \Closure::bind(function ($request) {
            return $this->verifyWebhook($request);
        }, $provider, get_class($provider));
        $this->assertTrue($verifyWebhook($request));
    }

    public function test_process_webhook_calls_verify_and_process_ticket()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();
        $mock = new class($prov) extends TicketTailorProvider {
            public function __construct(?\App\Models\TicketProvider $provider = null)
            {
                parent::__construct($provider);
            }

            protected function verifyWebhook(Request $request): bool
            {
                return true;
            }

            protected function processTicket(object $data): ?Ticket
            {
                return null;
            }
        };
        $request = Request::create('/webhook', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['payload' => ['id' => 'abc']]));
        $this->assertTrue($mock->processWebhook($request));
    }

    public function test_get_qr_code_returns_expected_url()
    {
        $provider = $this->getProvider();
        $data = (object)['barcode' => 'abc123'];
        $getQrCode = \Closure::bind(function ($data) {
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

    // TESTS Fail currently, TBC
    public function test_sync_tickets_removes_voided_and_adds_missing()
    {
        $provider = $this->getProvider();
        $prov = $provider->getProvider();

        // Existing ticket that should be removed (voided)
        $voided = \App\Models\Ticket::factory()->create([
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
        $mock = new class($prov) extends TicketTailorProvider {
            public array $stubTickets = [];

            public function __construct(?\App\Models\TicketProvider $provider = null)
            {
                parent::__construct($provider);
            }

            protected function getTickets(?string $address = null): array
            {
                return $this->stubTickets;
            }

            protected function makeTicket(?User $user, object $data): ?Ticket
            {
                return \App\Models\Ticket::factory()->create([
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
        $mock = new class($prov) extends TicketTailorProvider {
            public array $stubTickets = [];

            public function __construct(?\App\Models\TicketProvider $provider = null)
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
}
