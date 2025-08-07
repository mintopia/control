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
        // Mock makePermalink to return 'testclan'
        require_once base_path('app/helpers.php');
        $request = new ClanRequest();
        $mockClan = (object)['id' => 2];
        \App\Models\Clan::shouldReceive('whereCode')->with('testclan')->andReturnSelf();
        \App\Models\Clan::shouldReceive('first')->andReturn($mockClan);
        // Set the property dynamically to avoid undefined property error
        $request->clan = (object)['id' => 1];
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
            $this->assertEquals('That clan name is not available', $message);
        };
        $closure('name', 'Test Clan', $fail);
        $this->assertTrue($called, 'Fail closure was not called for existing clan');
    }

    public function testNameRuleClosurePassesIfNoClanExists()
    {
        require_once base_path('app/helpers.php');
        $request = new ClanRequest();
        \App\Models\Clan::shouldReceive('whereCode')->with('testclan')->andReturnSelf();
        \App\Models\Clan::shouldReceive('first')->andReturn(null);
        // Set the property dynamically to avoid undefined property error
        $request->clan = null;
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['name'] as $rule) {
            if ($rule instanceof \Closure) {
                $closure = $rule;
                break;
            }
        }
        $fail = function () {
            $this->fail('Fail closure should not be called when no clan exists');
        };
        $closure('name', 'Test Clan', $fail);
    }
}
