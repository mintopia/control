<?php

namespace Tests\Unit\app\Models;

use App\Models\SeatGroupAssignment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

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
        $this->assertInstanceOf(BelongsTo::class, $assignment->group());
    }
}
