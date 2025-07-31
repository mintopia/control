<?php

namespace Tests\Unit\app\Jobs;

use Tests\TestCase;
use App\Jobs\SyncTicketsForEmailJob;
use App\Models\EmailAddress;

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

    // FIXME this needs work
    // public function testHandleSyncsWithEnabledProvidersIfEmailVerified()
    // {
    //     $email = $this->getMockBuilder(EmailAddress::class)->disableOriginalConstructor()->getMock();
    //     $email->verified_at = now();

    //     // Testing debug for verified email
    //     // NOTE: copied from above, may not work as expected
    //     $logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)->getMock();
    //     $logger->expects($this->once())->method('debug')->with($this->stringContains('Synchronising tickets for'));
    //     \Illuminate\Support\Facades\Log::swap($logger);

    //     // Testing synchronisation with providers
    //     $user = $this->getMockBuilder(\App\Models\User::class)->disableOriginalConstructor()->getMock();
    //     $user->method('__toString')->willReturn('user2');
    //     $belongsTo = $this->getMockBuilder(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
    //         ->disableOriginalConstructor()
    //         ->getMock();
    //     $belongsTo->method('getResults')->willReturn($user);
    //     $email->method('user')->willReturn($belongsTo);
    //     $email->method('__toString')->willReturn('user@example.com');

    //     $job = new SyncTicketsForEmailJob($email);
    //     $job->handle();
    // }
}
