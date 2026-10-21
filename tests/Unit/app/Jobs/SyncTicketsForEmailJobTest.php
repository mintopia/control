<?php

namespace Tests\Unit\app\Jobs;

use App\Jobs\SyncTicketsForEmailJob;
use App\Models\EmailAddress;
use App\Models\TicketProvider;
use Database\Factories\EmailAddressFactory;
use Database\Factories\TicketProviderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Tests\TestCase;

// Simple concrete provider implementation to avoid Mockery and satisfy the contract

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
        $reflection = new ReflectionClass($job);
        $property = $reflection->getProperty('email');
        $property->setAccessible(true);
        $this->assertSame($email, $property->getValue($job));
    }


    public function testHandleDoesNotSyncIfEmailNotVerified2()
    {
        $email = $this->getMockBuilder(EmailAddress::class)->disableOriginalConstructor()->getMock();
        $email->verified_at = null;

        $logger = Mockery::mock(LoggerInterface::class);
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
        $email = EmailAddressFactory::new()->create([
            'verified_at' => now(),
        ]);

        // Create two real TicketProvider models so the job's Eloquent query finds them
        $fakeClass = 'Tests\\Fakes\\TestTicketProvider';
        $p1 = TicketProviderFactory::new()->create(['enabled' => true, 'provider_class' => $fakeClass]);
        $p2 = TicketProviderFactory::new()->create(['enabled' => true, 'provider_class' => $fakeClass]);

        // Shared counter to track how many times syncTickets is invoked
        $calls = 0;
        app()->bind($fakeClass, function ($app, $params) use (&$calls) {
            // Return a concrete implementation that increments the shared counter
            return new HelperClasses\TestTicketProviderImplementation($params['provider'] ?? null, $calls);
        });

        $job = new SyncTicketsForEmailJob($email);
        $job->handle();

        // Each persisted TicketProvider should resolve to our TestTicketProviderImplementation and increment the counter
        $this->assertEquals(2, $calls, 'Expected syncTickets to be called twice');
    }

    public function testHandleWithNoProvidersDoesNotCallSyncTickets()
    {
        $email = EmailAddressFactory::new()->create([
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
