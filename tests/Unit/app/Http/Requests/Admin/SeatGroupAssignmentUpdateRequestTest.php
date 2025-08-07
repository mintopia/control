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

    public function testRulesContainAssignmentTypeIdKey()
    {
        $request = $this->getMockBuilder(SeatGroupAssignmentUpdateRequest::class)
            ->onlyMethods(['input'])
            ->getMock();
        $request->expects($this->any())
            ->method('input')
            ->with('assignment_type')
            ->willReturn('user');
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
        $request = $this->getMockBuilder(SeatGroupAssignmentUpdateRequest::class)
            ->onlyMethods(['input'])
            ->getMock();
        $request->expects($this->any())
            ->method('input')
            ->with('assignment_type')
            ->willReturn('clan');
        $rules = $request->rules();
        $rule = $rules['assignment_type_id'];
        $this->assertStringContainsString('exists:clans,id', $rule);
    }
}
