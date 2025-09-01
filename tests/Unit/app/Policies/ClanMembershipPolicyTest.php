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
}
