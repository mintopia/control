<?php

namespace Tests\Unit\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\SyncDiscordRoles;

class SyncDiscordRolesTest extends TestCase
{
    public function testCanInstantiateCommand()
    {
        $command = new SyncDiscordRoles();
        $this->assertInstanceOf(SyncDiscordRoles::class, $command);
    }
}
