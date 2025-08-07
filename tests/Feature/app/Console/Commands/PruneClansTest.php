<?php

namespace Tests\Feature\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\PruneClans;
use App\Models\Clan;
use App\Models\User;
use App\Models\ClanRole;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PruneClansTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateCommand()
    {
        $command = new PruneClans();
        $this->assertInstanceOf(PruneClans::class, $command);
    }

    public function testPruneClansDeletesEmptyClans()
    {
        $clanWithMembers = Clan::factory()->create();
        $clanWithoutMembers = Clan::factory()->create();
        $user = User::factory()->create();
        $role = ClanRole::factory()->create(['code' => 'member']);
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
        $user = User::factory()->create();
        $role = ClanRole::factory()->create(['code' => 'member']);
        $clan->addUser($user, $role);

        $this->assertDatabaseCount('clans', 1);
        Artisan::call('control:prune-clans');
        $this->assertDatabaseCount('clans', 1);
    }

    public function testPruneClansDeletesMultipleEmptyClans()
    {
        $clans = Clan::factory()->count(3)->create();
        $user = User::factory()->create();
        $role = ClanRole::factory()->create(['code' => 'member']);
        $clans[0]->addUser($user, $role);

        $this->assertDatabaseCount('clans', 3);
        Artisan::call('control:prune-clans');
        $this->assertDatabaseCount('clans', 1);
        $this->assertDatabaseHas('clans', ['id' => $clans[0]->id]);
    }
}
