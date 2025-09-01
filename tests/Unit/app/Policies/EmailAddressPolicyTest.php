<?php

namespace Tests\Unit\app\Policies;

use Tests\TestCase;
use App\Policies\EmailAddressPolicy;
use App\Models\EmailAddress;
use App\Models\User;

class EmailAddressPolicyTest extends TestCase
{
    public function testUpdateReturnsTrueWhenUserOwnsEmailAddress()
    {
        $user = new User();
        $user->id = 1;
        $emailAddress = new EmailAddress(['user_id' => 1]);
        $policy = new EmailAddressPolicy();
        $this->assertTrue($policy->update($user, $emailAddress));
    }

    public function testUpdateReturnsFalseWhenUserDoesNotOwnEmailAddress()
    {
        $user = new User(['id' => 1]);
        $emailAddress = new EmailAddress(['user_id' => 2]);
        $policy = new EmailAddressPolicy();
        $this->assertFalse($policy->update($user, $emailAddress));
    }
}
