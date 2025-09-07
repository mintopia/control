<?php

namespace Tests\Unit\app\Services\Contracts;

use App\Models\EmailAddress;
use App\Models\TicketProvider;
use Illuminate\Http\Request;
use Tests\TestCase;

class TicketProviderContractTest extends TestCase
{
    public function test_config_mapping_returns_expected_array()
    {
        $provider = new HelperClasses\DummyTicketProvider();
        $mapping = $provider->configMapping();
        $this->assertArrayHasKey('apikey', $mapping);
        $this->assertEquals('API Key', $mapping['apikey']['name']);
        $this->assertEquals('dummy-key', $mapping['apikey']['value']);
    }

    public function test_install_returns_ticket_provider_instance()
    {
        $provider = new HelperClasses\DummyTicketProvider();
        $ticketProvider = $provider->install();
        $this->assertInstanceOf(TicketProvider::class, $ticketProvider);
        $this->assertEquals('Dummy', $ticketProvider->name);
        $this->assertEquals('dummy', $ticketProvider->code);
    }

    public function test_process_webhook_returns_true()
    {
        $provider = new HelperClasses\DummyTicketProvider();
        $request = Request::create('/webhook', 'POST');
        $this->assertTrue($provider->processWebhook($request));
    }

    public function test_sync_tickets_accepts_string_and_emailaddress()
    {
        $provider = new HelperClasses\DummyTicketProvider();
        $email = 'test@example.com';
        $emailAddress = new EmailAddress(['email' => $email]);
        $this->assertNull($provider->syncTickets($email));
        $this->assertNull($provider->syncTickets($emailAddress));
    }

    public function test_get_events_returns_expected_array()
    {
        $provider = new HelperClasses\DummyTicketProvider();
        $events = $provider->getEvents();
        $this->assertArrayHasKey('evt1', $events);
        $this->assertEquals('Event 1', $events['evt1']);
    }

    public function test_get_ticket_types_returns_expected_array()
    {
        $provider = new HelperClasses\DummyTicketProvider();
        $types = $provider->getTicketTypes('evt1');
        $this->assertArrayHasKey('type1', $types);
        $this->assertEquals('VIP', $types['type1']);
    }

    public function test_sync_all_tickets_accepts_null_output()
    {
        $provider = new HelperClasses\DummyTicketProvider();
        $this->assertNull($provider->syncAllTickets(null));
    }
}
