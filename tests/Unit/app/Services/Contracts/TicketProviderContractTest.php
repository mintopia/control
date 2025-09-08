<?php

namespace Tests\Unit\app\Services\Contracts;

use App\Models\EmailAddress;
use App\Models\TicketProvider;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Http\Request;
use Tests\Traits\ProviderTestHelpers;
use Tests\TestCase;

class TicketProviderContractTest extends TestCase
{
    use ProviderTestHelpers;

    public function testConfigMappingReturnsExpectedArray()
    {
        $provider = $this->makeTicketProvider();
        $this->assertImplementsInterface($provider, TicketProviderContract::class);
        $mapping = $provider->configMapping();
        $this->assertArrayHasKey('apikey', $mapping);
        $this->assertEquals('API Key', $mapping['apikey']['name']);
        $this->assertEquals('dummy-key', $mapping['apikey']['value']);
    }

    public function testInstallReturnsTicketProviderInstance()
    {
        $provider = $this->makeTicketProvider();
        $ticketProvider = $provider->install();
        $this->assertInstanceOf(TicketProvider::class, $ticketProvider);
        $this->assertEquals('Dummy', $ticketProvider->name);
        $this->assertEquals('dummy', $ticketProvider->code);
    }

    public function testProcessWebhookReturnsTrue()
    {
        $provider = $this->makeTicketProvider();
        $request = Request::create('/webhook', 'POST');
        $this->assertTrue($provider->processWebhook($request));
    }

    public function testSyncTicketsAcceptsStringAndEmailaddress()
    {
        $provider = $this->makeTicketProvider();
        $email = 'test@example.com';
        $emailAddress = new EmailAddress(['email' => $email]);
        $this->assertNull($provider->syncTickets($email));
        $this->assertNull($provider->syncTickets($emailAddress));
    }

    public function testGetEventsReturnsExpectedArray()
    {
        $provider = $this->makeTicketProvider();
        $events = $provider->getEvents();
        $this->assertArrayHasKey('evt1', $events);
        $this->assertEquals('Event 1', $events['evt1']);
    }

    public function testGetTicketTypesReturnsExpectedArray()
    {
        $provider = $this->makeTicketProvider();
        $types = $provider->getTicketTypes('evt1');
        $this->assertArrayHasKey('type1', $types);
        $this->assertEquals('VIP', $types['type1']);
    }

    public function testSyncAllTicketsAcceptsNullOutput()
    {
        $provider = $this->makeTicketProvider();
        $this->assertNull($provider->syncAllTickets(null));
    }
}
