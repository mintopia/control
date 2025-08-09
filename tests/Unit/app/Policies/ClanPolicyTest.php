<?php

namespace Tests\Unit\app\Policies;

use Tests\TestCase;
use App\Policies\ClanPolicy;
use App\Models\Clan;
use App\Models\User;

class ClanPolicyTest extends TestCase
{
    // CHECK whether we can unit-test this properly
    // public function testViewReturnsTrueIfUserIsMember()
    // {
    //     $user = new User(['id' => 1]);
    //     $clan = $this->getMockBuilder(Clan::class)->onlyMethods(['members'])->getMock();
    //     $members = new class {
    //         public function where($column, $value)
    //         {
    //             return $this;
    //         }
    //         public function count()
    //         {
    //             return 1;
    //         }
    //     };
    //     $clan->method('members')->willReturn($members);
    //     $policy = new ClanPolicy();
    //     $this->assertTrue($policy->view($user, $clan));
    // }

    // public function testViewReturnsFalseIfUserIsNotMember()
    // {
    //     $user = new User(['id' => 1]);
    //     $clan = $this->getMockBuilder(Clan::class)->onlyMethods(['members'])->getMock();
    //     $members = new class {
    //         public function where($column, $value)
    //         {
    //             return $this;
    //         }
    //         public function count()
    //         {
    //             return 0;
    //         }
    //     };
    //     $clan->method('members')->willReturn($members);
    //     $policy = new ClanPolicy();
    //     $this->assertFalse($policy->view($user, $clan));
    // }
}
