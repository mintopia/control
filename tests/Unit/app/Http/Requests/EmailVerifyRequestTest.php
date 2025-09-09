<?php

namespace Tests\Unit\app\Http\Requests;

use App\Exceptions\EmailVerificationException;
use App\Http\Requests\EmailVerifyRequest;
use App\Models\EmailAddress;
use Closure;
use Tests\TestCase;

class EmailVerifyRequestTest extends TestCase
{
    /** @var EmailVerifyRequest */
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        // create an anonymous subclass to avoid relying on HelperClasses stub
        $this->request = new class () extends EmailVerifyRequest {
        };
    }
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
        $request = $this->request;
        $mockEmail = $this->getMockBuilder(EmailAddress::class)
            ->onlyMethods(['checkCode'])
            ->getMock();
        $mockEmail->expects($this->once())->method('checkCode')->with('abc123');
        $request->emailaddress = $mockEmail;
        $rules = $request->rules();
        $closure = null;
        foreach ($rules['code'] as $rule) {
            if ($rule instanceof Closure) {
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
        $request = $this->request;
        $mockEmail = $this->getMockBuilder(EmailAddress::class)
            ->onlyMethods(['checkCode'])
            ->getMock();
        $mockEmail->expects($this->once())->method('checkCode')->with('badcode')
            ->willThrowException(new EmailVerificationException('Invalid code'));
        $request->emailaddress = $mockEmail;
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
            $this->assertEquals('Invalid code', $message);
        };
        $closure('code', 'badcode', $fail);
        $this->assertTrue($called, 'Fail closure was not called');
    }
}
