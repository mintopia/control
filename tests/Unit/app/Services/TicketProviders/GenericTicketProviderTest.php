<?php

namespace Tests\Unit\app\Services\TicketProviders;

use Tests\TestCase;
use App\Services\TicketProviders\GenericTicketProvider;
use App\Models\TicketProvider;
use App\Models\ProviderSetting;
use App\Models\EmailAddress;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class GenericTicketProviderTest extends TestCase
{
    protected function getProvider(array $settings = [])
    {
        $ticketProvider = TicketProvider::factory()->create([
            'name' => 'Generic Provider',
            'code' => 'generic',
            'provider_class' => GenericTicketProvider::class,
        ]);
        foreach ($settings as $code => $value) {
            ProviderSetting::factory()->create([
                'provider_id' => $ticketProvider->id,
                'code' => $code,
                'value' => $value,
            ]);
        }
        return new GenericTicketProvider($ticketProvider);
    }

    public function test_config_mapping_returns_expected_array()
    {
        $provider = $this->getProvider();
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('apikey', $mapping);
        $this->assertEquals('API Key', $mapping['apikey']->name);
        $this->assertTrue($mapping['apikey']->encrypted);
        $this->assertArrayHasKey('endpoint', $mapping);
        $this->assertEquals('Base URL', $mapping['endpoint']->name);
    }

    public function test_process_webhook_calls_process_ticket_and_returns_true()
    {
        $provider = $this->getProvider();
        $mock = Mockery::mock(GenericTicketProvider::class . '[processTicket]', [$provider->provider])->makePartial();
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('processTicket')->once()->andReturnNull();

        $request = Request::create('/webhook', 'POST', ['payload' => ['id' => 'abc']]);
        $this->assertTrue($mock->processWebhook($request));
    }

    public function test_get_events_returns_cached_data()
    {
        $provider = $this->getProvider();
        $key = "ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events";
        Cache::put($key, ['evt1' => 'Event 1'], 10);
        $events = $provider->getEvents();
        $this->assertEquals(['evt1' => 'Event 1'], $events);
    }

    public function test_get_events_fetches_from_api_and_caches()
    {
        $provider = $this->getProvider([
            'apikey' => 'key',
            'endpoint' => 'https://api.example.com',
        ]);
        Cache::forget("ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events");

        $mockClient = Mockery::mock(Client::class);
        $responseData = (object)[
            'events' => [
                (object)['id' => 'evt1', 'name' => 'Event 1'],
                (object)['id' => 'evt2', 'name' => 'Event 2'],
            ],
            'hasMore' => false,
        ];
        $mockClient->shouldReceive('get')->once()->with('events', Mockery::any())->andReturn(
            new Response(200, [], json_encode($responseData))
        );
        $providerReflection = new \ReflectionClass($provider);
        $clientProp = $providerReflection->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($provider, $mockClient);

        $events = $provider->getEvents();
        $this->assertEquals(['evt1' => 'Event 1', 'evt2' => 'Event 2'], $events);

        // Should now be cached
        $cached = Cache::get("ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events");
        $this->assertEquals($events, $cached);
    }

    public function test_get_ticket_types_returns_cached_data()
    {
        $provider = $this->getProvider();
        $eventId = 'evt-1';
        $key = "ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events.{$eventId}.tickettypes";
        Cache::put($key, ['type1' => 'VIP'], 10);
        $types = $provider->getTicketTypes($eventId);
        $this->assertEquals(['type1' => 'VIP'], $types);
    }

    public function test_get_ticket_types_fetches_from_api_and_caches()
    {
        $provider = $this->getProvider([
            'apikey' => 'key',
            'endpoint' => 'https://api.example.com',
        ]);
        $eventId = 'evt-1';
        $key = "ticketproviders.{$provider->provider->id}.{$provider->provider->cache_prefix}.events.{$eventId}.tickettypes";
        Cache::forget($key);

        $mockClient = Mockery::mock(Client::class);
        $responseData = (object)[
            'ticket_types' => [
                (object)['id' => 'type1', 'name' => 'VIP'],
                (object)['id' => 'type2', 'name' => 'Standard'],
            ],
        ];
        $mockClient->shouldReceive('get')->once()->with('tickettypes', Mockery::on(function ($arg) use ($eventId) {
            return isset($arg['query']['event']) && $arg['query']['event'] === $eventId;
        }))->andReturn(
            new Response(200, [], json_encode($responseData))
        );
        $providerReflection = new \ReflectionClass($provider);
        $clientProp = $providerReflection->getProperty('client');
        $clientProp->setAccessible(true);
        $clientProp->setValue($provider, $mockClient);

        $types = $provider->getTicketTypes($eventId);
        $this->assertEquals(['type1' => 'VIP', 'type2' => 'Standard'], $types);

        // Should now be cached
        $cached = Cache::get($key);
        $this->assertEquals($types, $cached);
    }
}
