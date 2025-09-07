<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use App\Http\Requests\Admin\EmailAddressUpdateRequest;
use Illuminate\Validation\Rules\Unique;
use ReflectionClass;
use Tests\TestCase;

class EmailAddressUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new EmailAddressUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsAddress()
    {
        $request = new EmailAddressUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('address', $rules);
    }

    public function testAddressRuleIncludesRequiredStringEmail()
    {
        $request = new EmailAddressUpdateRequest();
        $rule = $request->rules()['address'];
        $this->assertContains('required', $rule);
        $this->assertContains('string', $rule);
        $this->assertContains('email', $rule);
    }

    public function testAddressRuleIncludesUniqueRule()
    {
        $request = new EmailAddressUpdateRequest();
        $rule = $request->rules()['address'];
        $found = false;
        foreach ($rule as $r) {
            if ($r instanceof Unique || $r === 'unique:email_addresses,email') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Unique rule not found in address rules');
    }

    public function testUniqueRuleIgnoresCurrentEmailIdIfSet()
    {
        // Use a small subclass so we can declare the public $email property (avoids dynamic property deprecation)
        $request = new class extends EmailAddressUpdateRequest {
            public $email;
        };
        $request->email = (object)['id' => 42];
        $rule = $request->rules()['address'];
        $uniqueRule = null;
        foreach ($rule as $r) {
            if ($r instanceof Unique) {
                $uniqueRule = $r;
                break;
            }
        }
        $this->assertNotNull($uniqueRule, 'Unique rule not found in address rules');
        $reflection = new ReflectionClass($uniqueRule);
        $property = $reflection->getProperty('ignore');
        $property->setAccessible(true);
        $this->assertEquals(42, $property->getValue($uniqueRule));
    }
}
