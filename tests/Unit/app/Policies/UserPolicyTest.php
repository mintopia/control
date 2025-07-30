<?php

namespace Tests\Unit\app\Policies;

use Tests\TestCase;
use App\Policies\UserPolicy;
use App\Models\User;

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
        $user = new User(['id' => 1]);
        $other = new User(['id' => 2]);
        $policy = new UserPolicy();
        $this->assertFalse($policy->update($user, $other));
    }
}
