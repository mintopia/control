<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\SeatGroupAssignment;

class SeatGroupAssignmentTest extends TestCase
{
    public function testCanInstantiateSeatGroupAssignment()
    {
        $assignment = new SeatGroupAssignment();
        $this->assertInstanceOf(SeatGroupAssignment::class, $assignment);
    }

    public function testGroupRelationship()
    {
        $assignment = new SeatGroupAssignment();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $assignment->group());
    }
}
