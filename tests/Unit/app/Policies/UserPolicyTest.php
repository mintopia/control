<?php

namespace Tests\Unit\app\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    public function testUpdateReturnsTrueWhenUserIdsMatch()
    {
        $user = new User(['id' => 1]);
        $policy = new UserPolicy();
        $this->assertTrue($policy->update($user, $user));
    }

    public function testUpdateReturnsFalseWhenUserIdsDoNotMatch()
    {
        $user = new User();
        $user->id = 1;
        $other = new User();
        $other->id = 2;
        $policy = new UserPolicy();
        $this->assertFalse($policy->update($user, $other));
    }
}
