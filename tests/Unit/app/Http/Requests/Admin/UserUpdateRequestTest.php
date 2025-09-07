<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use App\Models\EmailAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use ReflectionClass;
use Tests\TestCase;

class UserUpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthorizeReturnsTrue()
    {
        $request = new HelperClasses\UserUpdateRequestStub();
        $request->user = (object)['id' => 1];
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsNickname()
    {
        $request = new HelperClasses\UserUpdateRequestStub();
        $request->user = (object)['id' => 1];
        $rules = $request->rules();
        $this->assertArrayHasKey('nickname', $rules);
    }

    public function testRulesContainAllExpectedKeys()
    {
        $request = new HelperClasses\UserUpdateRequestStub();
        $request->user = (object)['id' => 42];
        $rules = $request->rules();
        $expected = [
            'nickname',
            'name',
            'primary_email_id',
            'roles',
            'roles.*',
            'terms',
            'first_login',
            'suspended'
        ];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $rules);
        }
    }

    public function testNicknameRuleIncludesUniqueIgnore()
    {
        $request = new HelperClasses\UserUpdateRequestStub();
        $request->user = (object)['id' => 99];
        $rules = $request->rules();
        $nicknameRules = $rules['nickname'];
        $uniqueRule = null;
        foreach ($nicknameRules as $rule) {
            if ($rule instanceof Unique || $rule instanceof Rule) {
                $uniqueRule = $rule;
                break;
            }
        }
        $this->assertNotNull($uniqueRule, 'Unique rule not found in nickname rules');
        $reflection = new ReflectionClass($uniqueRule);
        $property = $reflection->getProperty('ignore');
        $property->setAccessible(true);
        $this->assertEquals(99, $property->getValue($uniqueRule));
    }

    public function testPrimaryEmailIdRuleIncludesExistsWithClosure()
    {
        $request = new HelperClasses\UserUpdateRequestStub();
        $request->user = (object)['id' => 7];
        $rules = $request->rules();
        $primaryRules = $rules['primary_email_id'];
        $existsRule = null;
        foreach ($primaryRules as $rule) {
            if ($rule instanceof Exists || $rule instanceof Rule) {
                $existsRule = $rule;
                break;
            }
        }
        $this->assertNotNull($existsRule, 'Exists rule not found in primary_email_id rules');
        // Closure is not directly accessible, but we can check the type
        $this->assertInstanceOf(Exists::class, $existsRule);
    }

    public function testMessagesReturnsCustomMessage()
    {
        $request = new HelperClasses\UserUpdateRequestStub();
        $request->user = (object)['id' => 1];
        $messages = $request->messages();
        $this->assertArrayHasKey('primary_email_id.exists', $messages);
        $this->assertEquals('The email address is not valid', $messages['primary_email_id.exists']);
    }

    public function testPrimaryEmailExistsRuleRestrictsToCurrentUser()
    {
        // Create two users and an email for user A
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $email = EmailAddress::factory()->create(['user_id' => $userA->id]);

        $request = new HelperClasses\UserUpdateRequestStub();
        // Simulate that the request is for user B
        $request->user = (object)['id' => $userB->id];

        $rules = $request->rules();
        $validator = Validator::make([
            'primary_email_id' => $email->id,
        ], $rules);

        $this->assertTrue($validator->fails(), 'Validator should fail when primary_email_id belongs to another user');
        $this->assertArrayHasKey('primary_email_id', $validator->errors()->toArray());
    }
}
