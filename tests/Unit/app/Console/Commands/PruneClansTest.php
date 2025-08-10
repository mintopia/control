<?php

namespace Tests\Unit\app\Console\Commands;

use App\Console\Commands\PruneClans;
use App\Models\Clan;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class PruneClansTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    // FIXME Cannot execute commands
    // public function testHandleOutputsZeroWhenNoClansToPrune()
    // {
    //     // Use Laravel's partialMock for the Clan model
    //     $clanMock = $this->partialMock(Clan::class, function ($mock) {
    //         $mock->shouldReceive('doesntHave')->with('members')->andReturnSelf();
    //         $mock->shouldReceive('count')->andReturn(0);
    //     });

    //     // Use Laravel's Artisan command testing
    //     $this->artisan('prune:clans')
    //         ->expectsOutput('0 clans with 0 members')
    //         ->assertExitCode(0);
    // }

    // public function testHandleDeletesClansWhenCountGreaterThanZero()
    // {
    //     $clanMock = $this->partialMock(Clan::class, function ($mock) {
    //         $mock->shouldReceive('doesntHave')->with('members')->andReturnSelf();
    //         $mock->shouldReceive('count')->andReturn(3);
    //         $mock->shouldReceive('delete')->once();
    //     });

    //     $command = App::make(PruneClans::class);
    //     $command = App::make(PruneClans::class);
    //     $outputMock = \Mockery::mock(\Symfony\Component\Console\Output\OutputInterface::class);
    //     // Use Laravel's Artisan command testing
    //     $this->artisan('prune:clans')
    //         ->expectsOutput('3 clans with 0 members')
    //         ->assertExitCode(0);
    // }
}
