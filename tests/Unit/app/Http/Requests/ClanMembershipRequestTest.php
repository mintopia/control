<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\ClanMembershipRequest;

class ClanMembershipRequestTest extends TestCase
{
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

    //FIXME Mockery for Clan does not work - Class already exists (loaded before?)
    // public function testCodeRuleClosureFailsIfNoClanFound()
    // {
    //     $clanMock = \Mockery::mock('alias:App\Models\Clan');
    //     $clanMock->shouldReceive('whereInviteCode')->with('ABC123')->andReturnSelf();
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
    //     $closure('code', 'abc123', $fail);
    //     $this->assertTrue($called, 'Fail closure was not called for invalid code');
    // }

    // public function testCodeRuleClosurePassesIfClanFound()
    // {
    //     $clanMock = \Mockery::mock('alias:App\Models\Clan');
    //     $clanMock->shouldReceive('whereInviteCode')->with('ABC123')->andReturnSelf();
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
    //         $this->fail('Fail closure should not be called when clan exists');
    //     };
    //     $closure('code', 'abc123', $fail);
    // }
}
