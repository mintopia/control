<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\SeatGroup;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatGroupTest extends TestCase
{
    public function testCanInstantiateSeatGroup()
    {
        $group = new SeatGroup();
        $this->assertInstanceOf(SeatGroup::class, $group);
    }

    public function testEventRelationshipIsBelongsTo()
    {
        $group = new SeatGroup();
        $this->assertInstanceOf(BelongsTo::class, $group->event());
    }

    public function testAssignmentsRelationshipIsHasMany()
    {
        $group = new SeatGroup();
        $this->assertInstanceOf(HasMany::class, $group->assignments());
    }

    public function testSeatsRelationshipIsHasMany()
    {
        $group = new SeatGroup();
        $this->assertInstanceOf(HasMany::class, $group->seats());
    }
}
