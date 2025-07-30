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
}
