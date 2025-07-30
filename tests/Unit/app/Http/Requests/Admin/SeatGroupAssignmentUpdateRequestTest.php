<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\SeatGroupAssignmentUpdateRequest;

class SeatGroupAssignmentUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new SeatGroupAssignmentUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsAssignmentType()
    {
        $request = new SeatGroupAssignmentUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('assignment_type', $rules);
    }
}
