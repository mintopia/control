<?php

namespace Tests\Unit\app\Policies;

use Tests\TestCase;
use App\Policies\ClanMembershipPolicy;
use App\Models\ClanMembership;
use App\Models\User;

class ClanMembershipPolicyTest extends TestCase
{
    public function testDeleteCallsCanDelete()
    {
        $user = new User();
        $clanMembership = $this->getMockBuilder(ClanMembership::class)->onlyMethods(['canDelete'])->getMock();
        $clanMembership->expects($this->once())->method('canDelete')->with($user)->willReturn(true);
        $policy = new ClanMembershipPolicy();
        $this->assertTrue($policy->delete($user, $clanMembership));
    }

    public function testUpdateReturnsFalseByDefault()
    {
        $user = new User();
        $clanMembership = new ClanMembership();
        $policy = new ClanMembershipPolicy();
        $this->assertFalse($policy->update($user, $clanMembership));
    }
}
