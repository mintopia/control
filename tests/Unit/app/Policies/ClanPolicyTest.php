<?php

namespace Tests\Unit\app\Policies;

use App\Models\Clan;
use App\Models\ClanMembership;
use App\Models\ClanRole;
use App\Models\User;
use App\Policies\ClanPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClanPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function testViewReturnsTrueIfUserIsMember()
    {
        $user = User::factory()->create();
        $clan = Clan::factory()->create();
        ClanMembership::factory()->create([
            'user_id' => $user->id,
            'clan_id' => $clan->id,
        ]);

        $policy = new ClanPolicy();
        $this->assertTrue($policy->view($user, $clan));
    }

    public function testViewReturnsFalseIfUserIsNotMember()
    {
        $user = User::factory()->create();
        $clan = Clan::factory()->create();

        $policy = new ClanPolicy();
        $this->assertFalse($policy->view($user, $clan));
    }

    public function testUpdateReturnsTrueForLeaderAndFalseForNonLeader()
    {
        // ensure leader role exists
        ClanRole::factory()->create(['code' => 'leader']);

        $leader = User::factory()->create();
        $member = User::factory()->create();
        $clan = Clan::factory()->create();

        // create leader membership
        ClanMembership::factory()->create([
            'user_id' => $leader->id,
            'clan_id' => $clan->id,
            'clan_role_id' => ClanRole::whereCode('leader')->first()->id,
        ]);

        // create regular member
        ClanMembership::factory()->create([
            'user_id' => $member->id,
            'clan_id' => $clan->id,
        ]);

        $policy = new ClanPolicy();
        $this->assertTrue($policy->update($leader, $clan));
        $this->assertFalse($policy->update($member, $clan));
    }
}
