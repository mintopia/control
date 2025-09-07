<?php

namespace Tests\Unit\app\Http\Requests;

use App\Http\Requests\ClanMembershipRequest;
use App\Models\Clan;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClanMembershipRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthorizeReturnsTrue()
    {
        $request = new ClanMembershipRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new ClanMembershipRequest();
        $this->assertIsArray($request->rules());
    }

    public function testRulesContainCodeWithRequiredAndString()
    {
        $request = new ClanMembershipRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('code', $rules);
        $codeRules = $rules['code'];
        $this->assertContains('required', $codeRules);
        $this->assertContains('string', $codeRules);
    }

    public function testCodeRuleClosureFailsIfNoClanFound()
    {
        // No clans in DB -> closure should fail
        $request = new ClanMembershipRequest();
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            $this->assertEquals('The invite code is invalid', $message);
        };
        $closure('code', 'abc123', $fail);
        $this->assertTrue($called, 'Fail closure was not called for invalid code');
    }

    public function testCodeRuleClosurePassesIfClanFound()
    {
        // Create a clan with invite_code ABC123 (closure uppercases the input)
        Clan::factory()->create(['invite_code' => 'ABC123']);

        $request = new ClanMembershipRequest();
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function () use (&$called) {
            $called = true;
        };
        $closure('code', 'abc123', $fail);
        $this->assertFalse($called, 'Fail closure was called even though clan exists');
    }
}
