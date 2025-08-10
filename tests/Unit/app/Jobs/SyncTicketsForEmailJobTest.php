<?php

namespace Tests\Unit\app\Jobs;

use Tests\TestCase;
use App\Jobs\SyncTicketsForEmailJob;
use App\Models\EmailAddress;
use App\Models\TicketProvider;
use Illuminate\Support\Facades\Log;
use Mockery;

class SyncTicketsForEmailJobTest extends TestCase
{
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

    public function testHandleDoesNotSyncIfEmailNotVerified()
    {
        $email = $this->getMockBuilder(EmailAddress::class)->disableOriginalConstructor()->getMock();
        $email->verified_at = null; // Simulate unverified email

        // Testing debug for unverified email
        $logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)->getMock();
        $logger->expects($this->once())->method('debug')->with($this->stringContains('Failed synchronising tickets, email is not confirmed'));
        \Illuminate\Support\Facades\Log::swap($logger);

        $job = new SyncTicketsForEmailJob($email);
        $job->handle();
    }

    // Leaving this here for an example with Mockery
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

    //FIXME Needs more work - Mock EmailAddress doesn't succeed properly
    // public function testHandleSyncsTicketsIfEmailVerified()
    // {
    //     $email = Mockery::mock(['alias' => EmailAddress::class]);
    //     $email->verified_at = now();

    //     // Mock TicketProvider::forEmail to return a list of providers
    //     $provider1 = Mockery::mock();
    //     $provider2 = Mockery::mock();

    //     $provider1->shouldReceive('syncTickets')->once();
    //     $provider2->shouldReceive('syncTickets')->once();

    //     // Mock the static method forEmail
    //     $ticketProviderMock = Mockery::mock(['alias' => TicketProvider::class]);
    //     $ticketProviderMock->shouldReceive('forEmail')
    //         ->with($email)
    //         ->andReturn([$provider1, $provider2]);

    //     $job = new SyncTicketsForEmailJob($email);
    //     $job->handle();
    // }

    // public function testHandleWithNoProvidersDoesNotCallSyncTickets()
    // {
    //     $email = Mockery::mock(['alias' => EmailAddress::class]);
    //     $email->verified_at = now();

    //     // Mock TicketProvider::forEmail to return an empty array
    //     $ticketProviderMock = Mockery::mock(['alias' => TicketProvider::class]);
    //     $ticketProviderMock->shouldReceive('forEmail')
    //         ->with($email)
    //         ->andReturn([]);

    //     // No providers, so syncTickets should not be called

    //     $job = new SyncTicketsForEmailJob($email);
    //     $job->handle();

    //     // No assertion needed, test will fail if syncTickets is called on a non-existent provider
    // }
}
