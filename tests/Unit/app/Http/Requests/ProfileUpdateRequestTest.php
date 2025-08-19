<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\ProfileUpdateRequest;

class ProfileUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new ProfileUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new ProfileUpdateRequest();
        $this->assertIsArray($request->rules());
    }

    public function testRulesContainNicknameAndNameKeys()
    {
        $request = new class extends ProfileUpdateRequest {
            public function user($guard = null)
            {
                return (object)['id' => 42];
            }
        };
        $rules = $request->rules();
        $this->assertArrayHasKey('nickname', $rules);
        $this->assertArrayHasKey('name', $rules);
    }

    public function testRulesNicknameUniqueIgnoresCurrentUserId()
    {
        $request = new class extends ProfileUpdateRequest {
            public function user($guard = null)
            {
                return (object)['id' => 99];
            }
        };
        $rules = $request->rules();
        $nicknameRules = $rules['nickname'];
        $uniqueRule = null;
        foreach ($nicknameRules as $rule) {
            if ($rule instanceof \Illuminate\Validation\Rules\Unique || $rule instanceof \Illuminate\Validation\Rule) {
                $uniqueRule = $rule;
                break;
            }
        }
        $this->assertNotNull($uniqueRule, 'Unique rule not found in nickname rules');
        // The ignore value is protected, so we use reflection
        $reflection = new \ReflectionClass($uniqueRule);
        $property = $reflection->getProperty('ignore');
        $property->setAccessible(true);
        $this->assertEquals(99, $property->getValue($uniqueRule));
    }
}
