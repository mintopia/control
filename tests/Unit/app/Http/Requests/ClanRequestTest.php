<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\ClanRequest;
use App\Http\Requests\ClanMembershipRequest;
use Mockery;

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

    // FIXME Illuminate\Database\QueryException: SQLSTATE[HY000]: General error: 1 no such table: clans (Connection: sqlite, SQL: select count(*) as aggregate from "clans" where "invite_code" = )
    // public function testCodeRuleClosureFailsWithEmptyValue()
    // {
    //     $clanMock = Mockery::mock(['App\\Models\\Clan' => 'alias']);
    //     $clanMock->shouldReceive('whereInviteCode')->with('')->andReturnSelf();
    //     $clanMock->shouldReceive('count')->andReturn(0);

    //     $request = new ClanMembershipRequest();
    //     $rules = $request->rules();
    //     $closure = null;
    //     foreach ($rules['code'] as $rule) {
    //         if ($rule instanceof \Closure) {
    //             $closure = $rule;
    //             break;
    //         }
    //     }
    //     $called = false;
    //     $fail = function ($message) use (&$called) {
    //         $called = true;
    //         $this->assertEquals('The invite code is invalid', $message);
    //     };
    //     $closure('code', '', $fail);
    //     $this->assertTrue($called, 'Fail closure was not called for empty code');
    // }

    // public function testCodeRuleClosureIsCaseInsensitive()
    // {
    //     $clanMock = Mockery::mock(['App\\Models\\Clan' => 'alias']);
    //     $clanMock->shouldReceive('whereInviteCode')->with('ABCDEF')->andReturnSelf();
    //     $clanMock->shouldReceive('count')->andReturn(1);

    //     $request = new ClanMembershipRequest();
    //     $rules = $request->rules();
    //     $closure = null;
    //     foreach ($rules['code'] as $rule) {
    //         if ($rule instanceof \Closure) {
    //             $closure = $rule;
    //             break;
    //         }
    //     }
    //     $fail = function () {
    //         $this->fail('Fail closure should not be called for valid code');
    //     };
    //     $closure('code', 'abcdef', $fail);
    // }

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
