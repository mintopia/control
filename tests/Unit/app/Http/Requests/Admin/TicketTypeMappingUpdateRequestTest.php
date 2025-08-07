<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\TicketTypeMappingUpdateRequest;

class TicketTypeMappingUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketTypeMappingUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsExternalId()
    {
        $request = new TicketTypeMappingUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('external_id', $rules);
    }

    public function testRulesReturnsArray()
    {
        $request = new TicketTypeMappingUpdateRequest();
        $rules = $request->rules();
        $this->assertIsArray($rules);
    }

    public function testExternalIdRuleContainsValidationStrings()
    {
        $request = new TicketTypeMappingUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('external_id', $rules);
        $this->assertStringContainsString('required', $rules['external_id']);
        $this->assertStringContainsString('string', $rules['external_id']);
    }

    public function testRulesDoesNotContainUnexpectedFields()
    {
        $request = new TicketTypeMappingUpdateRequest();
        $rules = $request->rules();
        $this->assertCount(1, $rules);
        $this->assertArrayHasKey('external_id', $rules);
    }

    public function testAuthorizeAlwaysTrue()
    {
        $request = new TicketTypeMappingUpdateRequest();
        $this->assertTrue($request->authorize());
    }
}
