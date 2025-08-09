<?php

namespace Tests\Unit\app\Observers;

use Tests\TestCase;
use App\Observers\ClanMembershipObserver;
use App\Models\ClanMembership;
use App\Models\SeatingPlan;
use Mockery;

class ClanMembershipObserverTest extends TestCase
{
    //FIXME Illuminate\Database\QueryException: SQLSTATE[HY000]: General error: 1 no such table: seating_plans (Connection: sqlite, SQL: select * from "seating_plans" where exists (select * from "seats" where "seating_plans"."id" = "seats"."seating_plan_id" and exists (select * from "tickets" where "seats"."ticket_id" = "tickets"."id" and exists (select * from "users" where "tickets"."user_id" = "users"."id" and exists (select * from "clan_memberships" where "users"."id" = "clan_memberships"."user_id" and "id" = 123)))))
    // We are still trying to connect to the DB somewhere - do we need to mock more?

    //     $observer = new \App\Observers\ClanMembershipObserver();
    //     $observer->saved($clanMembership);
    //     $this->assertTrue(true);
    // }

    // public function testDeletingCallsDelayedRevisionUpdateOnPlans()
    // {
    //     $seatingPlanMock = \Mockery::mock(['App\\Models\\SeatingPlan' => 'alias']);
    //     $builderMock = Mockery::mock('stdClass');
    //     $planMock = Mockery::mock('SeatingPlan');

    //     $planMock->shouldReceive('delayedRevisionUpdate')->once();
    //     $builderMock->shouldReceive('get')->andReturn(collect([$planMock]));
    //     $seatingPlanMock->shouldReceive('whereHas')->andReturn($builderMock);

    //     $clanMembership = new class extends ClanMembership {
    //         public $id = 456;
    //     };

    //     $observer = new ClanMembershipObserver();
    //     $observer->deleting($clanMembership);
    //     $this->assertTrue(true);
    // }

    // public function testSavedUpdatesPlansIfUserIdDirty()
    // {
    //     $seatingPlanMock = Mockery::mock(['App\\Models\\SeatingPlan' => 'alias']);
    //     $builderMock = Mockery::mock('stdClass');
    //     $planMock = Mockery::mock('App\\Models\\SeatingPlan');

    //     $planMock->shouldReceive('updateRevision')->once();
    //     $builderMock->shouldReceive('get')->andReturn(collect([$planMock]));
    //     $seatingPlanMock->shouldReceive('whereHas')->andReturn($builderMock);

    //     $clanMembership = new class extends \App\Models\ClanMembership {
    //         public $id = 123;
    //         public function isDirty($attributes = null)
    //         {
    //             return $attributes === 'user_id' || (is_array($attributes) && in_array('user_id', $attributes));
    //         }
    //     };


    public function testSavedDoesNothingIfUserIdNotDirty()
    {
        $clanMembership = new class extends ClanMembership {
            public function isDirty($attributes = null)
            {
                return false;
            }
        };

        $seatingPlanMock = \Mockery::mock(['App\\Models\\SeatingPlan' => 'alias']);
        $seatingPlanMock->shouldReceive('whereHas')->never();

        $observer = new ClanMembershipObserver();
        $observer->saved($clanMembership);
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
