<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use App\Http\Requests\Admin\TicketUpdateRequest;
use Tests\TestCase;

class TicketUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsReference()
    {
        $request = new TicketUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('reference', $rules);
    }

    public function testRulesContainAllExpectedKeys()
    {
        $request = new TicketUpdateRequest();
        $rules = $request->rules();
        $expected = ['reference', 'ticket_type_id', 'user_id'];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $rules);
        }
    }

    public function testReferenceRuleIncludesRequiredStringMax()
    {
        $request = new TicketUpdateRequest();
        $rule = $request->rules()['reference'];
        $this->assertStringContainsString('required', $rule);
        $this->assertStringContainsString('string', $rule);
        $this->assertStringContainsString('max:100', $rule);
    }

    public function testTicketTypeIdRuleIncludesRequiredIntegerExists()
    {
        $request = new TicketUpdateRequest();
        $rule = $request->rules()['ticket_type_id'];
        $this->assertStringContainsString('required', $rule);
        $this->assertStringContainsString('integer', (string)$rule);
        $this->assertStringContainsString('exists:ticket_types,id', $rule);
    }

    public function testUserIdRuleIncludesSometimesIntegerExistsNullable()
    {
        $request = new TicketUpdateRequest();
        $rule = $request->rules()['user_id'];
        $this->assertStringContainsString('sometimes', $rule);
        $this->assertStringContainsString('integer', (string)$rule);
        $this->assertStringContainsString('exists:users,id', $rule);
        $this->assertStringContainsString('nullable', $rule);
    }
}
