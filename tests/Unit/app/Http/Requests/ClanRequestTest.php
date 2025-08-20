<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\ClanRequest;
use App\Http\Requests\ClanMembershipRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClanRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthorizeReturnsTrue()
    {
        $request = new ClanRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new ClanRequest();
        $this->assertIsArray($request->rules());
    }

    public function testRulesContainNameWithRequiredStringMinMax()
    {
        $request = new ClanRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
        $nameRules = $rules['name'];
        $this->assertContains('required', $nameRules);
        $this->assertContains('string', $nameRules);
        $this->assertContains('min:3', $nameRules);
        $this->assertContains('max:100', $nameRules);
    }

    public function testNameRuleClosureFailsIfPermalinkEmpty()
    {
        // Mock makePermalink to return empty string
        \App\Http\Requests\ClanRequest::macro('makePermalink', function ($value) {
            return '';
        });
        $request = new ClanRequest();
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['name'] as $rule) {
            if ($rule instanceof \Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            $this->assertEquals('The clan name is not valid', $message);
        };
        $closure('name', '!!!', $fail);
        $this->assertTrue($called, 'Fail closure was not called for empty permalink');
    }

    public function testRulesContainCodeRuleClosure()
    {
        $request = new ClanMembershipRequest();
        $rules = $request->rules();
        $closureFound = false;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof \Closure) {
                $closureFound = true;
                break;
            }
        }
        $this->assertTrue($closureFound, 'Closure rule not found for code');
    }

    public function testCodeRuleClosureFailsWithEmptyValue()
    {
        // No clans in DB -> empty code should fail
        $request = new ClanMembershipRequest();
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof \Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function ($message) use (&$called) {
            $called = true;
            $this->assertEquals('The invite code is invalid', $message);
        };
        $closure('code', '', $fail);
        $this->assertTrue($called, 'Fail closure was not called for empty code');
    }

    public function testCodeRuleClosureIsCaseInsensitive()
    {
        // Create a clan with invite_code ABCDEF
        \App\Models\Clan::factory()->create(['invite_code' => 'ABCDEF']);
        $request = new ClanMembershipRequest();
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof \Closure) {
                $closure = $rule;
                break;
            }
        }
        $called = false;
        $fail = function () use (&$called) {
            $called = true;
        };
        $closure('code', 'abcdef', $fail);
        $this->assertFalse($called, 'Fail closure was called for valid code (case-insensitive check failed)');
    }

    public function testRulesArrayStructure()
    {
        $request = new ClanMembershipRequest();
        $rules = $request->rules();
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('code', $rules);
        $this->assertIsArray($rules['code']);
        $this->assertContains('required', $rules['code']);
        $this->assertContains('string', $rules['code']);
    }
}
