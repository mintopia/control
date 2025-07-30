<?php

namespace Tests\Unit\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\SyncTickets;

class SyncTicketsTest extends TestCase
{
    public function testCanInstantiateCommand()
    {
        $command = new SyncTickets();
        $this->assertInstanceOf(SyncTickets::class, $command);
    }
}
