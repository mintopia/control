<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\ClanMembershipObserver;
use App\Models\ClanMembership;
use App\Models\SeatingPlan;
use Mockery;

class ClanMembershipObserverTest extends TestCase
{
    public function testSavedUpdatesPlansIfUserIdDirty()
    {
        $clanMembership = $this->getMockBuilder(ClanMembership::class)
            ->onlyMethods(['isDirty', 'getAttribute'])
            ->getMock();
        $clanMembership->method('isDirty')->with('user_id')->willReturn(true);
        $clanMembership->id = 123;

        // Mock SeatingPlan and updateRevision
        $planMock = Mockery::mock(SeatingPlan::class);
        $planMock->shouldReceive('updateRevision')->once();

        // Mock query builder for whereHas and get
        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('whereId')->with(123)->andReturnSelf();

        $builderMock = Mockery::mock();
        $builderMock->shouldReceive('whereHas')->andReturnSelf();
        $builderMock->shouldReceive('get')->andReturn(collect([$planMock]));

        // Swap SeatingPlan::whereHas to our builder mock
        SeatingPlan::shouldReceive('whereHas')->andReturn($builderMock);

        $observer = new ClanMembershipObserver();
        $observer->saved($clanMembership);
        $this->assertTrue(true); // Just to ensure the method runs
    }

    public function testSavedDoesNothingIfUserIdNotDirty()
    {
        $clanMembership = $this->getMockBuilder(ClanMembership::class)
            ->onlyMethods(['isDirty'])
            ->getMock();
        $clanMembership->method('isDirty')->with('user_id')->willReturn(false);

        // SeatingPlan::whereHas should not be called
        SeatingPlan::shouldReceive('whereHas')->never();

        $observer = new ClanMembershipObserver();
        $observer->saved($clanMembership);
        $this->assertTrue(true);
    }

    public function testDeletingCallsDelayedRevisionUpdateOnPlans()
    {
        $clanMembership = new ClanMembership();
        $clanMembership->id = 456;

        // Mock SeatingPlan and delayedRevisionUpdate
        $planMock = Mockery::mock(SeatingPlan::class);
        $planMock->shouldReceive('delayedRevisionUpdate')->once();

        // Mock query builder for whereHas and get
        $queryMock = Mockery::mock();
        $queryMock->shouldReceive('whereId')->with(456)->andReturnSelf();

        $builderMock = Mockery::mock();
        $builderMock->shouldReceive('whereHas')->andReturnSelf();
        $builderMock->shouldReceive('get')->andReturn(collect([$planMock]));

        // Swap SeatingPlan::whereHas to our builder mock
        SeatingPlan::shouldReceive('whereHas')->andReturn($builderMock);

        $observer = new ClanMembershipObserver();
        $observer->deleting($clanMembership);
        $this->assertTrue(true);
    }

    public function testCreatedDoesNothing()
    {
        $clanMembership = new ClanMembership();
        $observer = new ClanMembershipObserver();
        $this->assertNull($observer->created($clanMembership));
    }

    public function testUpdatedDoesNothing()
    {
        $clanMembership = new ClanMembership();
        $observer = new ClanMembershipObserver();
        $this->assertNull($observer->updated($clanMembership));
    }

    public function testRestoredDoesNothing()
    {
        $clanMembership = new ClanMembership();
        $observer = new ClanMembershipObserver();
        $this->assertNull($observer->restored($clanMembership));
    }

    public function testForceDeletedDoesNothing()
    {
        $clanMembership = new ClanMembership();
        $observer = new ClanMembershipObserver();
        $this->assertNull($observer->forceDeleted($clanMembership));
    }
}
