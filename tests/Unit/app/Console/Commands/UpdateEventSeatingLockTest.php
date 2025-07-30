<?php

namespace Tests\Unit\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\UpdateEventSeatingLock;

class UpdateEventSeatingLockTest extends TestCase
{
    public function testCanInstantiateCommand()
    {
        $command = new UpdateEventSeatingLock();
        $this->assertInstanceOf(UpdateEventSeatingLock::class, $command);
    }
}
