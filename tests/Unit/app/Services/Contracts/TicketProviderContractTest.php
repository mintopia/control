<?php

namespace Tests\Unit\app\Services\Contracts;

use Tests\TestCase;
use App\Services\Contracts\TicketProviderContract;
use App\Models\TicketProvider;
use App\Models\EmailAddress;
use Illuminate\Http\Request;
use Illuminate\Console\OutputStyle;

class DummyTicketProvider implements TicketProviderContract
{
    public function __construct(?TicketProvider $provider = null) {}

    public function configMapping(): array
    {
        return [
            'apikey' => [
                'name' => 'API Key',
                'validation' => 'required|string',
                'value' => 'dummy-key',
            ],
        ];
    }

    public function install(): TicketProvider
    {
        return new TicketProvider(['name' => 'Dummy', 'code' => 'dummy']);
    }

    public function processWebhook(Request $request): bool
    {
        return true;
    }

    public function syncTickets(string|EmailAddress $email): void
    {
        // Dummy implementation
    }

    public function getEvents(): array
    {
        return ['evt1' => 'Event 1', 'evt2' => 'Event 2'];
    }

    public function getTicketTypes(string $eventExternalId): array
    {
        return ['type1' => 'VIP', 'type2' => 'Standard'];
    }

    public function syncAllTickets(?OutputStyle $output): void
    {
        // Dummy implementation
    }
}

class TicketProviderContractTest extends TestCase
{
    public function test_config_mapping_returns_expected_array()
    {
        $provider = new DummyTicketProvider();
        $mapping = $provider->configMapping();
        $this->assertArrayHasKey('apikey', $mapping);
        $this->assertEquals('API Key', $mapping['apikey']['name']);
        $this->assertEquals('dummy-key', $mapping['apikey']['value']);
    }

    public function test_install_returns_ticket_provider_instance()
    {
        $provider = new DummyTicketProvider();
        $ticketProvider = $provider->install();
        $this->assertInstanceOf(TicketProvider::class, $ticketProvider);
        $this->assertEquals('Dummy', $ticketProvider->name);
        $this->assertEquals('dummy', $ticketProvider->code);
    }

    public function test_process_webhook_returns_true()
    {
        $provider = new DummyTicketProvider();
        $request = Request::create('/webhook', 'POST');
        $this->assertTrue($provider->processWebhook($request));
    }

    public function test_sync_tickets_accepts_string_and_emailaddress()
    {
        $provider = new DummyTicketProvider();
        $email = 'test@example.com';
        $emailAddress = new EmailAddress(['email' => $email]);
        $this->assertNull($provider->syncTickets($email));
        $this->assertNull($provider->syncTickets($emailAddress));
    }

    public function test_get_events_returns_expected_array()
    {
        $provider = new DummyTicketProvider();
        $events = $provider->getEvents();
        $this->assertArrayHasKey('evt1', $events);
        $this->assertEquals('Event 1', $events['evt1']);
    }

    public function test_get_ticket_types_returns_expected_array()
    {
        $provider = new DummyTicketProvider();
        $types = $provider->getTicketTypes('evt1');
        $this->assertArrayHasKey('type1', $types);
        $this->assertEquals('VIP', $types['type1']);
    }

    public function test_sync_all_tickets_accepts_null_output()
    {
        $provider = new DummyTicketProvider();
        $this->assertNull($provider->syncAllTickets(null));
    }
}
