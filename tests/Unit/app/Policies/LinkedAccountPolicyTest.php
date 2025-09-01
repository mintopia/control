<?php

namespace Tests\Unit\app\Policies;

use Tests\TestCase;
use App\Policies\LinkedAccountPolicy;
use App\Models\LinkedAccount;
use App\Models\User;

class LinkedAccountPolicyTest extends TestCase
{
    public function testUpdateReturnsTrueWhenUserOwnsLinkedAccount()
    {
        $user = new User();
        $user->id = 1;
        $linkedAccount = new LinkedAccount(['user_id' => 1]);
        $policy = new LinkedAccountPolicy();
        $this->assertTrue($policy->update($user, $linkedAccount));
    }

    public function testUpdateReturnsFalseWhenUserDoesNotOwnLinkedAccount()
    {
        $user = new User(['id' => 1]);
        $linkedAccount = new LinkedAccount(['user_id' => 2]);
        $policy = new LinkedAccountPolicy();
        $this->assertFalse($policy->update($user, $linkedAccount));
    }
}
