<?php

namespace Tests\Feature\app\Services\TicketProviders;

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
use Mockery;
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

    // FIXME ReflectionMethod is deprectated, how to test?
    // public function test_verify_webhook_returns_true_if_no_secret()
    // {
    //     $provider = $this->getProvider();
    //     $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['payload' => []]));
    //     $method = new \ReflectionMethod($provider, 'verifyWebhook');
    //     $method->setAccessible(true);
    //     $this->assertTrue($method->invoke($provider, $request));
    // }

    // public function test_verify_webhook_throws_if_header_missing()
    // {
    //     $provider = $this->getProvider(['webhook_secret' => 'secret']);
    //     $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['payload' => []]));
    //     $method = new \ReflectionMethod($provider, 'verifyWebhook');
    //     $method->setAccessible(true);
    //     $this->expectException(\app\Exceptions\TicketProviderWebhookException::class);
    //     $method->invoke($provider, $request);
    // }

    // public function test_verify_webhook_throws_if_signature_invalid()
    // {
    //     $provider = $this->getProvider(['webhook_secret' => 'secret']);
    //     $header = 't=1234567890,v1=invalidsignature';
    //     $request = Request::create('/webhook', 'POST', [], [], [], ['HTTP_tickettailor-webhook-signature' => $header], 'body');
    //     $method = new \ReflectionMethod($provider, 'verifyWebhook');
    //     $method->setAccessible(true);
    //     $this->expectException(\app\Exceptions\TicketProviderWebhookException::class);
    //     $method->invoke($provider, $request);
    // }

    // public function test_verify_webhook_throws_if_timestamp_too_old()
    // {
    //     $provider = $this->getProvider(['webhook_secret' => 'secret']);
    //     $timestamp = now()->subMinutes(10)->timestamp;
    //     $body = 'body';
    //     $signature = hash_hmac('sha256', $timestamp . $body, 'secret');
    //     $header = "t={$timestamp},v1={$signature}";
    //     $request = Request::create('/webhook', 'POST', [], [], [], ['HTTP_tickettailor-webhook-signature' => $header], $body);
    //     $method = new \ReflectionMethod($provider, 'verifyWebhook');
    //     $method->setAccessible(true);
    //     $this->expectException(\app\Exceptions\TicketProviderWebhookException::class);
    //     $method->invoke($provider, $request);
    // }

    // public function test_verify_webhook_returns_true_on_valid_signature()
    // {
    //     $provider = $this->getProvider(['webhook_secret' => 'secret']);
    //     $timestamp = now()->timestamp;
    //     $body = 'body';
    //     $signature = hash_hmac('sha256', $timestamp . $body, 'secret');
    //     $header = "t={$timestamp},v1={$signature}";
    //     $request = Request::create('/webhook', 'POST', [], [], [], ['HTTP_tickettailor-webhook-signature' => $header], $body);
    //     $method = new \ReflectionMethod($provider, 'verifyWebhook');
    //     $method->setAccessible(true);
    //     $this->assertTrue($method->invoke($provider, $request));
    // }

    // FIXME processTicket is a protected method, how to test?
    // public function test_process_webhook_calls_verify_and_process_ticket()
    // {
    //     $provider = $this->getProvider();
    //     $mock = Mockery::mock(TicketTailorProvider::class . '[verifyWebhook,processTicket]', [$provider->provider])->makePartial();
    //     $mock->shouldAllowMockingProtectedMethods();
    //     $mock->shouldReceive('verifyWebhook')->once()->andReturn(true);
    //     $mock->shouldReceive('processTicket')->once()->andReturnNull();
    //     $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode(['payload' => ['id' => 'abc']]));
    //     $request->setJson(json_decode($request->getContent(), true));
    //     $this->assertTrue($mock->processWebhook($request));
    // }

    // public function test_get_qr_code_returns_expected_url()
    // {
    //     $provider = $this->getProvider();
    //     $data = (object)['barcode' => 'abc123'];
    //     $method = new \ReflectionMethod($provider, 'getQrCode');
    //     $method->setAccessible(true);
    //     $url = $method->invoke($provider, $data);
    //     $this->assertStringContainsString('abc123', $url);
    //     $this->assertStringStartsWith('https://api.qrserver.com/v1/create-qr-code/', $url);
    // }

    // FIXME provider is a protected property, how to test?
    // public function test_get_events_returns_cached_data()
    // {
    //     $provider = $this->getProvider();
    //     $key = "ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events";
    //     Cache::put($key, ['evt1' => 'Event 1'], 10);
    //     $events = $provider->getEvents();
    //     $this->assertEquals(['evt1' => 'Event 1'], $events);
    // }

    // public function test_get_ticket_types_returns_cached_data()
    // {
    //     $provider = $this->getProvider();
    //     $eventId = 'evt-1';
    //     $key = "ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events.{$eventId}.tickettypes";
    //     Cache::put($key, ['type1' => 'VIP'], 10);
    //     $types = $provider->getTicketTypes($eventId);
    //     $this->assertEquals(['type1' => 'VIP'], $types);
    // }
}
