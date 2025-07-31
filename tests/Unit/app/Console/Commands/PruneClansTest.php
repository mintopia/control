<?php

namespace Tests\Unit\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\PruneClans;
use App\Models\Clan;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PruneClansTest extends TestCase
{
    use RefreshDatabase;

    // TODO Tests do not work with the current setup, need to fix
    public function testCanInstantiateCommand()
    {
        $command = new PruneClans();
        $this->assertInstanceOf(PruneClans::class, $command);
    }

    public function testPruneClansDeletesEmptyClans()
    {
        $clanWithMembers = Clan::factory()->create();
        $clanWithoutMembers = Clan::factory()->create();
        // Add a member to the first clan
        $user = \App\Models\User::factory()->create();
        $role = \App\Models\ClanRole::factory()->create(['code' => 'member']);
        $clanWithMembers->addUser($user, $role);

        $this->assertDatabaseCount('clans', 2);
        $this->assertDatabaseHas('clans', ['id' => $clanWithMembers->id]);
        $this->assertDatabaseHas('clans', ['id' => $clanWithoutMembers->id]);

        Artisan::call('control:prune-clans');

        $this->assertDatabaseHas('clans', ['id' => $clanWithMembers->id]);
        $this->assertDatabaseMissing('clans', ['id' => $clanWithoutMembers->id]);
    }

    public function testPruneClansDoesNothingIfNoEmptyClans()
    {
        $clan = Clan::factory()->create();
        $user = \App\Models\User::factory()->create();
        $role = \App\Models\ClanRole::factory()->create(['code' => 'member']);
        $clan->addUser($user, $role);

        $this->assertDatabaseCount('clans', 1);
        Artisan::call('control:prune-clans');
        $this->assertDatabaseCount('clans', 1);
    }

    public function testPruneClansDeletesMultipleEmptyClans()
    {
        $clans = Clan::factory()->count(3)->create();
        $user = \App\Models\User::factory()->create();
        $role = \App\Models\ClanRole::factory()->create(['code' => 'member']);
        $clans[0]->addUser($user, $role);

        $this->assertDatabaseCount('clans', 3);
        Artisan::call('control:prune-clans');
        $this->assertDatabaseCount('clans', 1);
        $this->assertDatabaseHas('clans', ['id' => $clans[0]->id]);
    }
}
