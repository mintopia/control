<?php

namespace Tests\Unit\app\Policies;

use Tests\TestCase;
use App\Policies\EventPolicy;
use App\Models\Event;
use App\Models\User;

class EventPolicyTest extends TestCase
{
    public function testSeeReturnsTrueIfEventNotDraft()
    {
        $user = new User();
        $event = new Event(['draft' => false]);
        $policy = new EventPolicy();
        $this->assertTrue($policy->see($user, $event));
    }

    public function testSeeReturnsTrueIfUserIsAdminOrManagerAndEventIsDraft()
    {
        $user = $this->getMockBuilder(User::class)->onlyMethods(['hasAnyRole'])->getMock();
        $user->method('hasAnyRole')->with(['admin', 'manager'])->willReturn(true);
        $event = new Event(['draft' => true]);
        $policy = new EventPolicy();
        $this->assertTrue($policy->see($user, $event));
    }

    public function testSeeReturnsTrueIfUserIsAdminAndEventIsDraft()
    {
        $user = $this->getMockBuilder(User::class)->onlyMethods(['hasAnyRole'])->getMock();
        // Simulate user is admin
        $user->method('hasAnyRole')->with(['admin', 'manager'])->willReturnCallback(function($roles) {
            return in_array('admin', $roles);
        });
        $event = new Event(['draft' => true]);
        $policy = new EventPolicy();
        $this->assertTrue($policy->see($user, $event));
    }

    public function testSeeReturnsTrueIfUserIsManagerAndEventIsDraft()
    {
        $user = $this->getMockBuilder(User::class)->onlyMethods(['hasAnyRole'])->getMock();
        // Simulate user is manager
        $user->method('hasAnyRole')->with(['admin', 'manager'])->willReturnCallback(function($roles) {
            return in_array('manager', $roles);
        });
        $event = new Event(['draft' => true]);
        $policy = new EventPolicy();
        $this->assertTrue($policy->see($user, $event));
    }

    public function testSeeReturnsFalseIfUserIsNotAdminOrManagerAndEventIsDraft()
    {
        $user = $this->getMockBuilder(User::class)->onlyMethods(['hasAnyRole'])->getMock();
        // Simulate user is not admin
        $user->method('hasAnyRole')->with(['admin', 'manager'])->willReturn(false);
        $event = new Event(['draft' => true]);
        $policy = new EventPolicy();
        $this->assertFalse($policy->see($user, $event));
    }
}
