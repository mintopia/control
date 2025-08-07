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
    /*
    * Illuminate\Database\QueryException: could not find driver (Connection: mysql, SQL: select exists (select 1 from information_schema.tables where table_schema = schema() and table_name = 'migrations' and table_type in ('BASE TABLE', 'SYSTEM VERSIONED')) as `exists`)
    * This error indicates that the database connection is not properly configured or the required database driver is not installed.
    * Ensure that your .env file has the correct database connection settings and that the necessary PHP extensions are enabled.
    */

    // use RefreshDatabase;

    // public function testCanInstantiateCommand()
    // {
    //     $command = new PruneClans();
    //     $this->assertInstanceOf(PruneClans::class, $command);
    // }

    // public function testPruneClansDeletesEmptyClans()
    // {
    //     $clanWithMembers = Clan::factory()->create();
    //     $clanWithoutMembers = Clan::factory()->create();
    //     $user = User::factory()->create();
    //     $role = ClanRole::factory()->create(['code' => 'member']);
    //     $clanWithMembers->addUser($user, $role);

    //     $this->assertDatabaseCount('clans', 2);
    //     $this->assertDatabaseHas('clans', ['id' => $clanWithMembers->id]);
    //     $this->assertDatabaseHas('clans', ['id' => $clanWithoutMembers->id]);

    //     Artisan::call('control:prune-clans');

    //     $this->assertDatabaseHas('clans', ['id' => $clanWithMembers->id]);
    //     $this->assertDatabaseMissing('clans', ['id' => $clanWithoutMembers->id]);
    // }

    // public function testPruneClansDoesNothingIfNoEmptyClans()
    // {
    //     $clan = Clan::factory()->create();
    //     $user = User::factory()->create();
    //     $role = ClanRole::factory()->create(['code' => 'member']);
    //     $clan->addUser($user, $role);

    //     $this->assertDatabaseCount('clans', 1);
    //     Artisan::call('control:prune-clans');
    //     $this->assertDatabaseCount('clans', 1);
    // }

    // public function testPruneClansDeletesMultipleEmptyClans()
    // {
    //     $clans = Clan::factory()->count(3)->create();
    //     $user = User::factory()->create();
    //     $role = ClanRole::factory()->create(['code' => 'member']);
    //     $clans[0]->addUser($user, $role);

    //     $this->assertDatabaseCount('clans', 3);
    //     Artisan::call('control:prune-clans');
    //     $this->assertDatabaseCount('clans', 1);
    //     $this->assertDatabaseHas('clans', ['id' => $clans[0]->id]);
    // }

}
