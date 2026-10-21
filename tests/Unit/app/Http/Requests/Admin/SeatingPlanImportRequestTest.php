<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use App\Http\Requests\Admin\SeatingPlanImportRequest;
use Tests\TestCase;

class SeatingPlanImportRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new SeatingPlanImportRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsCsv()
    {
        $request = new SeatingPlanImportRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('csv', $rules);
    }

    public function testRulesContainWipeKey()
    {
        $request = new SeatingPlanImportRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('wipe', $rules);
    }

    public function testCsvRuleIncludesRequiredAndFile()
    {
        $request = new SeatingPlanImportRequest();
        $rule = $request->rules()['csv'];
        $this->assertStringContainsString('required', $rule);
        $this->assertStringContainsString('file', $rule);
    }

    public function testWipeRuleIncludesSometimesBooleanNullable()
    {
        $request = new SeatingPlanImportRequest();
        $rule = $request->rules()['wipe'];
        $this->assertStringContainsString('sometimes', $rule);
        $this->assertStringContainsString('boolean', $rule);
        $this->assertStringContainsString('nullable', $rule);
    }
}
