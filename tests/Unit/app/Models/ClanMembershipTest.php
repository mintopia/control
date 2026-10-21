<?php

namespace Tests\Unit\app\Models;

use App\Models\Clan;
use App\Models\ClanMembership;
use App\Models\ClanRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClanMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function testCanDeleteWhenNotLeaderAndWhenLeaderWithOtherLeader()
    {
        $clan = Clan::factory()->create();
        $leaderRole = ClanRole::factory()->create(['code' => 'leader']);
        $memberRole = ClanRole::factory()->create(['code' => 'member']);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // leader membership
        $m1 = ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user1->id, 'clan_role_id' => $leaderRole->id]);
        // another leader
        $m2 = ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $user2->id, 'clan_role_id' => $leaderRole->id]);

        $this->assertTrue($m1->canDelete($user1));

        // single leader scenario
        $m2->delete();
        $this->assertFalse($m1->canDelete($user1));

        // non-leader can delete own membership
        $member = ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => User::factory()->create()->id, 'clan_role_id' => $memberRole->id]);
        $this->assertTrue($member->canDelete($member->user));
    }

    public function testCanDeleteWhenCalledByOtherLeader()
    {
        $clan = Clan::factory()->create();
        $leaderRole = ClanRole::factory()->create(['code' => 'leader']);

        $leader = User::factory()->create();
        $other = User::factory()->create();

        $m1 = ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $other->id, 'clan_role_id' => $leaderRole->id]);
        $mLeader = ClanMembership::factory()->create(['clan_id' => $clan->id, 'user_id' => $leader->id, 'clan_role_id' => $leaderRole->id]);

        $this->assertTrue($m1->canDelete($leader));
    }
}
