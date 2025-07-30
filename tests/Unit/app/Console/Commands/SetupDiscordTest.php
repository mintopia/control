<?php

namespace Tests\Unit\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\SetupDiscord;

class SetupDiscordTest extends TestCase
{
    public function testCanInstantiateCommand()
    {
        $command = new SetupDiscord();
        $this->assertInstanceOf(SetupDiscord::class, $command);
    }
}
