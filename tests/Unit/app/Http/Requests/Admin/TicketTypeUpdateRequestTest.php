<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\TicketTypeUpdateRequest;

class TicketTypeUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketTypeUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsName()
    {
        $request = new TicketTypeUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
    }

    public function testRulesReturnsArray()
    {
        $request = new TicketTypeUpdateRequest();
        $rules = $request->rules();
        $this->assertIsArray($rules);
    }

    public function testNameRuleContainsValidationStrings()
    {
        $request = new TicketTypeUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('string', $rules['name']);
    }

    public function testRulesDoesNotContainUnexpectedFields()
    {
        $request = new TicketTypeUpdateRequest();
        $rules = $request->rules();
        $this->assertCount(1, $rules);
        $this->assertArrayHasKey('name', $rules);
    }

    public function testAuthorizeAlwaysTrue()
    {
        $request = new TicketTypeUpdateRequest();
        $this->assertTrue($request->authorize());
    }
}
