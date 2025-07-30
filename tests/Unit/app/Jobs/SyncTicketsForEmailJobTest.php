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
}
