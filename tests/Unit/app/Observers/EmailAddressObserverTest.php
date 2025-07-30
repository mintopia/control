<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\EmailAddressObserver;
use App\Models\EmailAddress;
use App\Models\User;

class EmailAddressObserverTest extends TestCase
{
    public function testCreatedAssociatesPrimaryEmailIfMissing()
    {
        $user = $this->getMockBuilder(User::class)->onlyMethods(['primaryEmail', 'save'])->getMock();
        $user->primaryEmail = null;
        $user->expects($this->once())->method('primaryEmail')->willReturnSelf();
        $user->expects($this->once())->method('save');
        $emailAddress = new EmailAddress();
        $emailAddress->user = $user;
        $observer = new EmailAddressObserver();
        $observer->created($emailAddress);
    }
}
