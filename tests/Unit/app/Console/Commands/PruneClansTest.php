<?php

namespace Tests\Unit\app\Console\Commands;

use App\Console\Commands\PruneClans;
use App\Models\Clan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class PruneClansTest extends TestCase
{
    use RefreshDatabase;

    public function testHandleOutputsZeroWhenNoClansToPrune()
    {
        // No clans exist in the database
        $this->assertSame(0, Clan::count());

        $command = App::make(PruneClans::class);
        $command->setLaravel($this->app);
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->run($input, $output);
        $this->assertStringContainsString('0 clans with 0 members', trim($output->fetch()));
    }

    public function testHandleDeletesClansWhenCountGreaterThanZero()
    {
        // Create 3 clans with no members
        Clan::factory()->count(3)->create();
        $this->assertSame(3, Clan::count());

        $command = App::make(PruneClans::class);
        $command->setLaravel($this->app);
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->run($input, $output);
        $this->assertStringContainsString('3 clans with 0 members', trim($output->fetch()));

        // After running the command, those clans should be removed
        $this->assertSame(0, Clan::count());
    }
}
