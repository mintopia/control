<?php

namespace Tests\Unit\app\Policies;

use Tests\TestCase;
use App\Policies\ClanMembershipPolicy;
use App\Models\ClanMembership;
use App\Models\User;
use App\Models\ClanRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClanMembershipPolicyTest extends TestCase
{
    use RefreshDatabase;
    public function testDeleteReturnsTrueWhenUserIsMemberAndNotLeader()
    {
        // Ensure a leader role exists because the policy/methods query it
        ClanRole::factory()->create(['code' => 'leader']);
        $memberRole = ClanRole::factory()->create(['code' => 'member']);

        $user = User::factory()->create();
        $membership = ClanMembership::factory()->create([
            'user_id' => $user->id,
            'clan_role_id' => $memberRole->id,
        ]);

        $policy = new ClanMembershipPolicy();
        $this->assertTrue($policy->delete($user, $membership));
    }

    public function testUpdateReturnsFalseWhenUserIsNotLeader()
    {
        ClanRole::factory()->create(['code' => 'leader']);
        $memberRole = ClanRole::factory()->create(['code' => 'member']);

        $user = User::factory()->create();
        $membership = ClanMembership::factory()->create([
            'user_id' => $user->id,
            'clan_role_id' => $memberRole->id,
        ]);

        $policy = new ClanMembershipPolicy();
        $this->assertFalse($policy->update($user, $membership));
    }

    public function testUpdateReturnsTrueWhenOtherUserIsLeader()
    {
        // leader role must exist
        ClanRole::factory()->create(['code' => 'leader']);
        $memberRole = ClanRole::factory()->create(['code' => 'member']);

        $clan = \App\Models\Clan::factory()->create();

        $owner = User::factory()->create();
        $leader = User::factory()->create();

        // membership for the target user (owner)
        $membership = ClanMembership::factory()->create([
            'user_id' => $owner->id,
            'clan_id' => $clan->id,
            'clan_role_id' => $memberRole->id,
        ]);

        // leader membership for another user in the same clan
        ClanMembership::factory()->create([
            'user_id' => $leader->id,
            'clan_id' => $clan->id,
            'clan_role_id' => ClanRole::whereCode('leader')->first()->id,
        ]);

        $policy = new ClanMembershipPolicy();
        $this->assertTrue($policy->update($leader, $membership));
    }

    public function testUpdateLeaderSelfSingleAndMultipleLeaders()
    {
        ClanRole::factory()->create(['code' => 'leader']);

        $clan = \App\Models\Clan::factory()->create();

        // single leader case
        $leader1 = User::factory()->create();
        $m1 = ClanMembership::factory()->create([
            'user_id' => $leader1->id,
            'clan_id' => $clan->id,
            'clan_role_id' => ClanRole::whereCode('leader')->first()->id,
        ]);

        $policy = new ClanMembershipPolicy();
        // only one leader in clan, updating self should return false
        $this->assertFalse($policy->update($leader1, $m1));

        // now add a second leader and expect true
        $leader2 = User::factory()->create();
        ClanMembership::factory()->create([
            'user_id' => $leader2->id,
            'clan_id' => $clan->id,
            'clan_role_id' => ClanRole::whereCode('leader')->first()->id,
        ]);

        // refresh membership count and evaluate again
        $this->assertTrue($policy->update($leader1, $m1));
    }
}
