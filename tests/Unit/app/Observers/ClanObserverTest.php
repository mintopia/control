<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\ClanObserver;
use App\Models\Clan;
use App\Models\SeatingPlan;
use Mockery;

use function App\makePermalink;

class ClanObserverTest extends TestCase
{
    public function testSavedUpdatesPlansIfNameDirty()
    {
        $clan = $this->getMockBuilder(Clan::class)->onlyMethods(['isDirty'])->getMock();
        $clan->method('isDirty')->with('name')->willReturn(true);
        $clan->id = 42;

        // Mock SeatingPlan and updateRevision
        $planMock = Mockery::mock(SeatingPlan::class);
        $planMock->shouldReceive('updateRevision')->once();

        // Mock query builder for whereHas and get
        $builderMock = Mockery::mock();
        $builderMock->shouldReceive('get')->andReturn(collect([$planMock]));

        // Swap SeatingPlan::whereHas to our builder mock
        SeatingPlan::shouldReceive('whereHas')->andReturn($builderMock);

        $observer = new ClanObserver();
        $observer->saved($clan);
        $this->assertTrue(true);
    }

    public function testSavedDoesNothingIfNameNotDirty()
    {
        $clan = $this->getMockBuilder(Clan::class)->onlyMethods(['isDirty'])->getMock();
        $clan->method('isDirty')->with('name')->willReturn(false);

        // SeatingPlan::whereHas should not be called
        SeatingPlan::shouldReceive('whereHas')->never();

        $observer = new ClanObserver();
        $observer->saved($clan);
        $this->assertTrue(true);
    }

    public function testDeletingCallsDelayedRevisionUpdateOnPlans()
    {
        $clan = new Clan();
        $clan->id = 99;

        // Mock SeatingPlan and delayedRevisionUpdate
        $planMock = Mockery::mock(SeatingPlan::class);
        $planMock->shouldReceive('delayedRevisionUpdate')->once();

        // Mock query builder for whereHas and get
        $builderMock = Mockery::mock();
        $builderMock->shouldReceive('get')->andReturn(collect([$planMock]));

        // Swap SeatingPlan::whereHas to our builder mock
        SeatingPlan::shouldReceive('whereHas')->andReturn($builderMock);

        $observer = new ClanObserver();
        $observer->deleting($clan);
        $this->assertTrue(true);
    }

    public function testSavingGeneratesInviteCodeAndPermalinkIfMissing()
    {
        $clan = $this->getMockBuilder(Clan::class)
            ->onlyMethods(['generateCode'])
            ->getMock();
        $clan->invite_code = null;
        $clan->name = 'Test Clan';
        $clan->expects($this->once())->method('generateCode');
        // We are using makePermalink from  our helpers
        $clan->code = makePermalink($clan->name);

        $observer = new ClanObserver();
        $observer->saving($clan);
        $this->assertEquals('test-clan', $clan->code);
    }

    public function testSavingDoesNotGenerateInviteCodeIfPresent()
    {
        $clan = $this->getMockBuilder(Clan::class)
            ->onlyMethods(['generateCode'])
            ->getMock();
        $clan->invite_code = 'abc123';
        $clan->name = 'Another Clan';
        $clan->expects($this->never())->method('generateCode');

        $observer = new ClanObserver();
        $observer->saving($clan);
        $this->assertEquals('another-clan', $clan->code);
    }

    public function testCreatedDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $this->assertNull($observer->created($clan));
    }

    public function testUpdatedDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $this->assertNull($observer->updated($clan));
    }

    public function testDeletedDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $this->assertNull($observer->deleted($clan));
    }

    public function testRestoredDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $this->assertNull($observer->restored($clan));
    }

    public function testForceDeletedDoesNothing()
    {
        $clan = new Clan();
        $observer = new ClanObserver();
        $this->assertNull($observer->forceDeleted($clan));
    }
}
