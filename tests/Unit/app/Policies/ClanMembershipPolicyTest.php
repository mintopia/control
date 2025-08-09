<?php

namespace Tests\Unit\app\Policies;

use Tests\TestCase;
use App\Policies\ClanMembershipPolicy;
use App\Models\ClanMembership;
use App\Models\User;

class ClanMembershipPolicyTest extends TestCase
{
    // CHECK whether we can unit-test this properly
    // protected function setUp(): void
    // {
    //     parent::setUp();
    //     // Run the migrations to create all tables, including clan_roles
    //     $this->artisan('migrate');
    //     // Optionally, seed the clan_roles table if needed
    //     // \App\Models\ClanRole::factory()->create(['code' => 'leader']);
    // }

    // public function testDeleteCallsCanDelete()
    // {
    //     $user = new User();
    //     $clanMembership = $this->getMockBuilder(ClanMembership::class)->onlyMethods(['canDelete'])->getMock();
    //     $clanMembership->expects($this->once())->method('canDelete')->with($user)->willReturn(true);
    //     $policy = new ClanMembershipPolicy();
    //     $this->assertTrue($policy->delete($user, $clanMembership));
    // }

    // public function testUpdateReturnsFalseByDefault()
    // {
    //     $user = new User();
    //     $clanMembership = new ClanMembership();
    //     $policy = new ClanMembershipPolicy();
    //     $this->assertFalse($policy->update($user, $clanMembership));
    // }
}
