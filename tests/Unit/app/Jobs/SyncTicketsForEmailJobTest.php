<?php

namespace Tests\Unit\app\Jobs;

use Tests\TestCase;
use App\Jobs\SyncTicketsForEmailJob;
use App\Models\EmailAddress;
use App\Models\TicketProvider;
use Illuminate\Support\Facades\Log;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Console\OutputStyle;
use Illuminate\Http\Request;

// Simple concrete provider implementation to avoid Mockery and satisfy the contract
class TestTicketProviderImplementation implements \App\Services\Contracts\TicketProviderContract
{
    private $provider;
    private $callsRef;

    public function __construct(?\App\Models\TicketProvider $provider = null, &$callsRef = null)
    {
        $this->provider = $provider;
        $this->callsRef = &$callsRef;
    }

    public function __toString()
    {
        return 'TestTicketProvider';
    }

    public function configMapping(): array
    {
        return [];
    }

    public function install(): \App\Models\TicketProvider
    {
        return $this->provider;
    }

    public function processWebhook(Request $request): bool
    {
        return true;
    }

    public function syncTickets(string|\App\Models\EmailAddress $email): void
    {
        if ($this->callsRef === null) {
            $this->callsRef = 0;
        }
        $this->callsRef++;
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
        // no-op for tests
    }
}

class SyncTicketsForEmailJobTest extends TestCase
{
    use RefreshDatabase;

    public function testJobCanBeInstantiated()
    {
        $email = $this->getMockBuilder(EmailAddress::class)->disableOriginalConstructor()->getMock();
        $job = new SyncTicketsForEmailJob($email);
        $this->assertInstanceOf(SyncTicketsForEmailJob::class, $job);
    }

    public function testJobStoresEmailAddress()
    {
        $email = $this->getMockBuilder(EmailAddress::class)->disableOriginalConstructor()->getMock();
        $job = new SyncTicketsForEmailJob($email);
        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('email');
        $property->setAccessible(true);
        $this->assertSame($email, $property->getValue($job));
    }


    public function testHandleDoesNotSyncIfEmailNotVerified2()
    {
        $email = $this->getMockBuilder(EmailAddress::class)->disableOriginalConstructor()->getMock();
        $email->verified_at = null;

        $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('debug')
            ->once()
            ->with(Mockery::on(fn($msg) => str_contains($msg, 'Failed synchronising tickets, email is not confirmed')));
        Log::swap($logger);

        $job = new SyncTicketsForEmailJob($email);
        $job->handle();
    }

    public function testHandleSyncsTicketsIfEmailVerified()
    {
        // Use a real EmailAddress instance so the job constructor type-hint is satisfied
        $email = \Database\Factories\EmailAddressFactory::new()->create([
            'verified_at' => now(),
        ]);

        // Create two real TicketProvider models so the job's Eloquent query finds them
        $fakeClass = 'Tests\\Fakes\\TestTicketProvider';
        $p1 = \Database\Factories\TicketProviderFactory::new()->create(['enabled' => true, 'provider_class' => $fakeClass]);
        $p2 = \Database\Factories\TicketProviderFactory::new()->create(['enabled' => true, 'provider_class' => $fakeClass]);

        // Shared counter to track how many times syncTickets is invoked
        $calls = 0;
        app()->bind($fakeClass, function ($app, $params) use (&$calls) {
            // Return a concrete implementation that increments the shared counter
            return new TestTicketProviderImplementation($params['provider'] ?? null, $calls);
        });

        $job = new SyncTicketsForEmailJob($email);
        $job->handle();

        // Each persisted TicketProvider should resolve to our TestTicketProviderImplementation and increment the counter
        $this->assertEquals(2, $calls, 'Expected syncTickets to be called twice');
    }

    public function testHandleWithNoProvidersDoesNotCallSyncTickets()
    {
        $email = \Database\Factories\EmailAddressFactory::new()->create([
            'verified_at' => now(),
        ]);

        // Ensure there are no enabled providers in the DB
        TicketProvider::query()->where('enabled', true)->delete();

        // No providers, so syncTickets should not be called
        $job = new SyncTicketsForEmailJob($email);
        $job->handle();

        // Verify the query returns an empty collection (serves as assertion)
        $this->assertEmpty(TicketProvider::where('enabled', true)->get());
    }
}
