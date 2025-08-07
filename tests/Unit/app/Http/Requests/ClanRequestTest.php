<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\ClanRequest;

class ClanRequestTest extends TestCase
{
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

    public function testNameRuleClosureFailsIfClanExistsAndNotCurrent()
    {
        // Unable to mock static methods with current Mockery version
        $this->fail('Static method mocking for App\\Models\\Clan is not supported in this environment.');
    }

    public function testNameRuleClosurePassesIfNoClanExists()
    {
        // Unable to mock static methods with current Mockery version
        $this->fail('Static method mocking for App\\Models\\Clan is not supported in this environment.');
    }
}
