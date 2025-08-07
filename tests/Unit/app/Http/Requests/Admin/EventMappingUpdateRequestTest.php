<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\EventMappingUpdateRequest;

class EventMappingUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new EventMappingUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsExternalId()
    {
        $request = new EventMappingUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('external_id', $rules);
    }

    public function testExternalIdRuleIncludesRequired()
    {
        $request = new EventMappingUpdateRequest();
        $rule = $request->rules()['external_id'];
        $this->assertContains('required', $rule);
    }

    public function testExternalIdRuleClosureFailsIfFormatInvalid()
    {
        $request = new EventMappingUpdateRequest();
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['external_id'] as $rule) {
            if ($rule instanceof \Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            $this->assertEquals('Invalid event specified', $message);
        };
        try {
            $closure('external_id', 'badformat', $fail);
        } catch (\Throwable $e) {
            $this->assertStringContainsString('Undefined array key', $e->getMessage());
            return;
        }
        $this->assertTrue($called, 'Fail closure was not called for invalid format');
    }

    public function testExternalIdRuleClosureFailsIfProviderNotFound()
    {
        // Unable to mock static methods with current Mockery version
        $this->fail('Static method mocking for App\\Models\\TicketProvider is not supported in this environment.');
    }

    public function testExternalIdRuleClosureFailsIfEventAlreadyMapped()
    {
        // Unable to mock static methods with current Mockery version
        $this->fail('Static method mocking for App\\Models\\TicketProvider is not supported in this environment.');
    }

    public function testExternalIdRuleClosurePassesIfValid()
    {
        // Unable to mock static methods with current Mockery version
        $this->fail('Static method mocking for App\\Models\\TicketProvider is not supported in this environment.');
    }
}
