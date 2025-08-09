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
        $belongsToMock = \Mockery::mock(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        $belongsToMock->shouldReceive('getResults')->once()->andReturn(null);
        $belongsToMock->shouldReceive('associate')->once();

        $user = \Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('primaryEmail')->once()->andReturn($belongsToMock);
        $user->shouldReceive('save')->once();

        $emailAddress = new EmailAddress();
        $emailAddress->user_id = $user->id;
        $emailAddress->setRelation('user', $user);

        $observer = new EmailAddressObserver();
        $observer->created($emailAddress);
    }
}
