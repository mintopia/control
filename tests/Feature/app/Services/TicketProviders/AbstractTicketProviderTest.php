<?php

namespace Tests\Feature\app\Services\TicketProviders;

use App\Models\TicketProvider;
use App\Models\ProviderSetting;
use App\Services\TicketProviders\AbstractTicketProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class DummyTicketProvider extends AbstractTicketProvider
{
    protected string $name = 'Dummy Provider';
    protected string $code = 'dummy';

    public function __construct($provider = null)
    {
        parent::__construct($provider);
    }

    public function configMapping(): array
    {
        return [
            'apikey' => (object)[
                'name' => 'API Key',
                'validation' => 'required|string',
                'value' => 'test-key',
            ],
            'webhook_secret' => (object)[
                'name' => 'Webhook Secret',
                'validation' => 'required|string',
                'value' => 'secret',
            ],
        ];
    }
}

class AbstractTicketProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_mapping_returns_expected_array()
    {
        $provider = new DummyTicketProvider();
        $mapping = $provider->configMapping();

        $this->assertArrayHasKey('apikey', $mapping);
        $this->assertEquals('API Key', $mapping['apikey']->name);
        $this->assertArrayHasKey('webhook_secret', $mapping);
        $this->assertEquals('Webhook Secret', $mapping['webhook_secret']->name);
    }

    public function test_install_creates_ticket_provider_and_settings()
    {
        $provider = new DummyTicketProvider();
        $ticketProvider = $provider->install();

        $this->assertInstanceOf(TicketProvider::class, $ticketProvider);
        $this->assertEquals('Dummy Provider', $ticketProvider->name);
        $this->assertEquals('dummy', $ticketProvider->code);

        $settings = $ticketProvider->settings()->pluck('code')->toArray();
        $this->assertContains('apikey', $settings);
        $this->assertContains('webhook_secret', $settings);
    }

    public function test_install_does_not_duplicate_provider()
    {
        $provider = new DummyTicketProvider();
        $first = $provider->install();
        $second = $provider->install();

        $this->assertEquals($first->id, $second->id);
        $this->assertCount(1, TicketProvider::whereCode('dummy')->get());
    }

    // FIXME SyncDiscordRoles may be missing some settings
    // public function test_install_settings_updates_existing_settings()
    // {
    //     $provider = new DummyTicketProvider();
    //     $ticketProvider = TicketProvider::factory()->create([
    //         'name' => 'Dummy Provider',
    //         'code' => 'dummy',
    //         'provider_class' => DummyTicketProvider::class,
    //     ]);
    //     $providerSetting = ProviderSetting::factory()->create([
    //         'provider_id' => $ticketProvider->id,
    //         'code' => 'apikey',
    //         'name' => 'Old Name',
    //     ]);
    //     $provider = new DummyTicketProvider($ticketProvider);
    //     $provider->installSettings();

    //     $providerSetting->refresh();
    //     $this->assertEquals('API Key', $providerSetting->name);
    // }

    public function test_process_webhook_returns_true()
    {
        $provider = new DummyTicketProvider();
        $request = Request::create('/webhook', 'POST');
        $this->assertTrue($provider->processWebhook($request));
    }

    public function test_get_events_returns_empty_array()
    {
        $provider = new DummyTicketProvider();
        $this->assertEquals([], $provider->getEvents());
    }

    public function test_get_ticket_types_returns_empty_array()
    {
        $provider = new DummyTicketProvider();
        $this->assertEquals([], $provider->getTicketTypes('event-id'));
    }

    public function test_sync_tickets_does_nothing()
    {
        $provider = new DummyTicketProvider();
        $this->assertNull($provider->syncTickets('test@example.com'));
    }

    public function test_sync_all_tickets_does_nothing()
    {
        $provider = new DummyTicketProvider();
        $this->assertNull($provider->syncAllTickets(null));
    }
}
