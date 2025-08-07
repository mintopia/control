<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\EmailVerifyRequest;

class EmailVerifyRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new EmailVerifyRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new EmailVerifyRequest();
        $this->assertIsArray($request->rules());
    }

    public function testRulesContainCodeWithRequiredStringAndAlphaNum()
    {
        $request = new EmailVerifyRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('code', $rules);
        $codeRules = $rules['code'];
        $this->assertContains('required', $codeRules);
        $this->assertContains('string', $codeRules);
        $this->assertContains('alpha_num:ascii', $codeRules);
    }

    public function testCodeRuleClosurePassesIfNoException()
    {
        $request = new EmailVerifyRequest();
        $mockEmail = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['checkCode'])
            ->getMock();
        $mockEmail->expects($this->once())->method('checkCode')->with('abc123');
        // Set the property dynamically to avoid undefined property error
        $request->emailaddress = $this->getMockBuilder(\App\Models\EmailAddress::class)
            ->onlyMethods(['checkCode'])
            ->getMock();
        $request->emailaddress->expects($this->once())->method('checkCode')->with('abc123');
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof \Closure) {
                $closure = $rule;
                break;
            }
        }
        $fail = function () {
            $this->fail('Closure should not call fail if no exception');
        };
        $closure('code', 'abc123', $fail);
    }

    public function testCodeRuleClosureFailsOnException()
    {
        $request = new EmailVerifyRequest();
        $mockEmail = $this->getMockBuilder(\App\Models\EmailAddress::class)
            ->onlyMethods(['checkCode'])
            ->getMock();
        $mockEmail->expects($this->once())->method('checkCode')->with('badcode')
            ->willThrowException(new \App\Exceptions\EmailVerificationException('Invalid code'));
        // Set the property dynamically to avoid undefined property error
        $request->emailaddress = $mockEmail;
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
            $this->assertEquals('Invalid code', $message);
        };
        $closure('code', 'badcode', $fail);
        $this->assertTrue($called, 'Fail closure was not called');
    }
}
