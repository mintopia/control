<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use App\Http\Requests\Admin\SeatGroupUpdateRequest;
use Tests\TestCase;

class SeatGroupUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new SeatGroupUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsName()
    {
        $request = new SeatGroupUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
    }

    public function testRulesContainClassKey()
    {
        $request = new SeatGroupUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('class', $rules);
    }

    public function testNameRuleIncludesRequiredStringMax()
    {
        $request = new SeatGroupUpdateRequest();
        $rule = $request->rules()['name'];
        $this->assertStringContainsString('required', $rule);
        $this->assertStringContainsString('string', $rule);
        $this->assertStringContainsString('max:100', $rule);
    }

    public function testClassRuleIncludesSometimesStringNullableMax()
    {
        $request = new SeatGroupUpdateRequest();
        $rule = $request->rules()['class'];
        $this->assertStringContainsString('sometimes', $rule);
        $this->assertStringContainsString('string', $rule);
        $this->assertStringContainsString('nullable', $rule);
        $this->assertStringContainsString('max:255', $rule);
    }
}
