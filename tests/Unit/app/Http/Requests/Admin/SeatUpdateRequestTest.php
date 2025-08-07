<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\SeatUpdateRequest;

class SeatUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new SeatUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsX()
    {
        $request = new SeatUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('x', $rules);
    }

    public function testRulesContainAllExpectedKeys()
    {
        $request = new SeatUpdateRequest();
        $rules = $request->rules();
        $expected = [
            'x',
            'y',
            'row',
            'number',
            'label',
            'description',
            'class',
            'seat_group',
            'disabled'
        ];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $rules);
        }
    }

    public function testXRuleIncludesRequiredIntegerMin()
    {
        $request = new SeatUpdateRequest();
        $rule = $request->rules()['x'];
        $this->assertStringContainsString('required', $rule);
        $this->assertStringContainsString('integer', $rule);
        $this->assertStringContainsString('min:0', $rule);
    }

    public function testYRuleIncludesRequiredIntegerMin()
    {
        $request = new SeatUpdateRequest();
        $rule = $request->rules()['y'];
        $this->assertStringContainsString('required', $rule);
        $this->assertStringContainsString('integer', $rule);
        $this->assertStringContainsString('min:0', $rule);
    }

    public function testNumberRuleIncludesRequiredIntegerMin()
    {
        $request = new SeatUpdateRequest();
        $rule = $request->rules()['number'];
        $this->assertStringContainsString('required', $rule);
        $this->assertStringContainsString('integer', $rule);
        $this->assertStringContainsString('min:0', $rule);
    }

    public function testRowRuleIncludesRequiredStringMax()
    {
        $request = new SeatUpdateRequest();
        $rule = $request->rules()['row'];
        $this->assertStringContainsString('required', $rule);
        $this->assertStringContainsString('string', $rule);
        $this->assertStringContainsString('max:8', $rule);
    }

    public function testLabelRuleIncludesRequiredStringMax()
    {
        $request = new SeatUpdateRequest();
        $rule = $request->rules()['label'];
        $this->assertStringContainsString('required', $rule);
        $this->assertStringContainsString('string', $rule);
        $this->assertStringContainsString('max:100', $rule);
    }

    public function testDescriptionRuleIncludesSometimesStringNullableMax()
    {
        $request = new SeatUpdateRequest();
        $rule = $request->rules()['description'];
        $this->assertStringContainsString('sometimes', $rule);
        $this->assertStringContainsString('string', $rule);
        $this->assertStringContainsString('nullable', $rule);
        $this->assertStringContainsString('max:255', $rule);
    }

    public function testClassRuleIncludesSometimesStringNullableMax()
    {
        $request = new SeatUpdateRequest();
        $rule = $request->rules()['class'];
        $this->assertStringContainsString('sometimes', $rule);
        $this->assertStringContainsString('string', $rule);
        $this->assertStringContainsString('nullable', $rule);
        $this->assertStringContainsString('max:255', $rule);
    }

    public function testSeatGroupRuleIncludesSometimesExistsNullable()
    {
        $request = new SeatUpdateRequest();
        $rule = $request->rules()['seat_group'];
        $this->assertStringContainsString('sometimes', $rule);
        $this->assertStringContainsString('exists:seat_groups,id', $rule);
        $this->assertStringContainsString('nullable', $rule);
    }

    public function testDisabledRuleIncludesSometimesBooleanNullable()
    {
        $request = new SeatUpdateRequest();
        $rule = $request->rules()['disabled'];
        $this->assertStringContainsString('sometimes', $rule);
        $this->assertStringContainsString('boolean', $rule);
        $this->assertStringContainsString('nullable', $rule);
    }
}
