<?php

namespace Tests\Unit\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\PruneClans;

class PruneClansTest extends TestCase
{
    public function testCanInstantiateCommand()
    {
        $command = new PruneClans();
        $this->assertInstanceOf(PruneClans::class, $command);
    }
}
