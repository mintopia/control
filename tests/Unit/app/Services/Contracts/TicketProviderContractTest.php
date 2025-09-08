<?php

namespace Tests\Unit\app\Services\Contracts;

use App\Models\EmailAddress;
use App\Models\TicketProvider;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Http\Request;
use Illuminate\Console\OutputStyle;
use Tests\Traits\ProviderTestHelpers;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketProviderContractTest extends TestCase
{
    use RefreshDatabase;
    use ProviderTestHelpers;

    public function testConfigMappingReturnsExpectedArray()
    {
        $provider = $this->makeTicketProvider();
        $this->assertImplementsInterface($provider, TicketProviderContract::class);
        $mapping = $provider->configMapping();
        $this->assertArrayHasKey('apikey', $mapping);
        // ProviderTestHelpers returns mapping entries as objects; assert accordingly
        $this->assertEquals('API Key', $mapping['apikey']->name ?? $mapping['apikey']['name']);
        $this->assertEquals('dummy-key', $mapping['apikey']->value ?? $mapping['apikey']['value']);
    }

    protected TicketProviderContract $basicProviderStub;

    protected function setUp(): void
    {
        parent::setUp();

        // Reusable lightweight stub provider used by multiple tests to avoid duplication.
        $this->basicProviderStub = new class (null) implements TicketProviderContract {
            public function __construct(?TicketProvider $provider = null)
            {
            }
            public function configMapping(): array
            {
                return [];
            }
            public function install(): TicketProvider
            {
                return new TicketProvider(['name' => 'Stub', 'code' => 'stub']);
            }
            public function processWebhook(Request $request): bool
            {
                return true;
            }
            public function syncTickets(string|EmailAddress $email): void
            {
                return;
            }
            public function getEvents(): array
            {
                return [];
            }
            public function getTicketTypes(string $eventExternalId): array
            {
                return [];
            }
            public function syncAllTickets(?OutputStyle $output): void
            {
                return;
            }
        };
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
        $provider = $this->basicProviderStub;
        $request = Request::create('/webhook', 'POST');
        $this->assertTrue($provider->processWebhook($request));
    }

    public function testSyncTicketsAcceptsStringAndEmailaddress()
    {
        $provider = $this->basicProviderStub;

        $email = 'test@example.com';
        $emailAddress = new EmailAddress(['email' => $email]);
        $this->assertNull($provider->syncTickets($email));
        $this->assertNull($provider->syncTickets($emailAddress));
    }

    public function testGetEventsReturnsExpectedArray()
    {
        // Create a small stub provider that implements the contract and returns expected events
        $provider = new class (null) implements TicketProviderContract {
            public function __construct(?TicketProvider $provider = null)
            {
            }
            public function configMapping(): array
            {
                return [];
            }
            public function install(): TicketProvider
            {
                return new TicketProvider(['name' => 'Stub', 'code' => 'stub']);
            }
            public function processWebhook(Request $request): bool
            {
                return true;
            }
            public function syncTickets(string|EmailAddress $email): void
            {
                return;
            }
            public function getEvents(): array
            {
                return ['evt1' => 'Event 1'];
            }
            public function getTicketTypes(string $eventExternalId): array
            {
                return [];
            }
            public function syncAllTickets(?OutputStyle $output): void
            {
                return;
            }
        };

        $events = $provider->getEvents();
        $this->assertArrayHasKey('evt1', $events);
        $this->assertEquals('Event 1', $events['evt1']);
    }

    public function testGetTicketTypesReturnsExpectedArray()
    {
        // Create a stub provider returning expected types
        $provider = new class (null) implements TicketProviderContract {
            public function __construct(?TicketProvider $provider = null)
            {
            }
            public function configMapping(): array
            {
                return [];
            }
            public function install(): TicketProvider
            {
                return new TicketProvider(['name' => 'Stub', 'code' => 'stub']);
            }
            public function processWebhook(Request $request): bool
            {
                return true;
            }
            public function syncTickets(string|EmailAddress $email): void
            {
                return;
            }
            public function getEvents(): array
            {
                return [];
            }
            public function getTicketTypes(string $eventExternalId): array
            {
                return ['type1' => 'VIP'];
            }
            public function syncAllTickets(?OutputStyle $output): void
            {
                return;
            }
        };

        $types = $provider->getTicketTypes('evt1');
        $this->assertArrayHasKey('type1', $types);
        $this->assertEquals('VIP', $types['type1']);
    }

    public function testSyncAllTicketsAcceptsNullOutput()
    {
        $provider = $this->basicProviderStub;

        $this->assertNull($provider->syncAllTickets(null));
    }
}
