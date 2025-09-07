<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use App\Http\Requests\Admin\SeatGroupAssignmentUpdateRequest;
use Tests\TestCase;

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

    public function testRulesContainAssignmentTypeIdKey()
    {
        // Use a small subclass to provide the input value without mocking framework methods
        $request = new class extends SeatGroupAssignmentUpdateRequest {
            public function input($key = null, $default = null)
            {
                if ($key === 'assignment_type') {
                    return 'user';
                }
                return $default;
            }
        };
        $rules = $request->rules();
        $this->assertArrayHasKey('assignment_type_id', $rules);
    }

    public function testAssignmentTypeRuleIsRequired()
    {
        $request = new SeatGroupAssignmentUpdateRequest();
        $rule = $request->rules()['assignment_type'];
        $this->assertStringContainsString('required', $rule);
    }

    public function testAssignmentTypeIdRuleIncludesExistsTable()
    {
        $request = new class extends SeatGroupAssignmentUpdateRequest {
            public function input($key = null, $default = null)
            {
                if ($key === 'assignment_type') {
                    return 'clan';
                }
                return $default;
            }
        };
        $rules = $request->rules();
        $rule = $rules['assignment_type_id'];
        $this->assertStringContainsString('exists:clans,id', $rule);
    }
}
