<?php

namespace Tests\Unit\app\Http\Requests;

use App\Http\Requests\UserSignupRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use ReflectionClass;
use ReflectionException;
use Tests\TestCase;

class UserSignupRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure any static cache on Setting is cleared between tests so each test is isolated.
        // Reset the protected static::$cached property via reflection.
        try {
            $settingClass = Setting::class;
            $ref = new ReflectionClass($settingClass);
            if ($ref->hasProperty('cached')) {
                $prop = $ref->getProperty('cached');
                $prop->setAccessible(true);
                $prop->setValue([]);
            }
        } catch (ReflectionException $e) {
            // ignore - best effort
        }
        // Also clear application cache used by Setting::fetch
        Cache::flush();
    }

    public function testAuthorizeReturnsTrue()
    {
        $request = new UserSignupRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        // Use a real user and set it as the currently authenticated user so
        // FormRequest->user() returns an Eloquent model.
        $user = User::factory()->create();
        $this->be($user);
        $request = new UserSignupRequest();
        $request->setUserResolver(fn() => $user);
        $this->assertIsArray($request->rules());
    }

    public function testMessagesReturnsCombinedMessageIfTermsAndPrivacySet()
    {
        // create settings in the DB so Setting::fetch reads them
        Setting::create(['code' => 'terms', 'name' => 'Terms', 'value' => true]);
        Setting::create(['code' => 'privacypolicy', 'name' => 'Privacy', 'value' => true]);
        // clear any cached value
        Setting::first()->clearCache();
        $request = new UserSignupRequest();
        $messages = $request->messages();
        $this->assertArrayHasKey('terms.accepted', $messages);
        $this->assertStringContainsString('Terms and Conditions', $messages['terms.accepted']);
        $this->assertStringContainsString('Privacy Policy', $messages['terms.accepted']);
    }

    public function testMessagesReturnsMessageIfOnlyTermsSet()
    {
        Setting::create(['code' => 'terms', 'name' => 'Terms', 'value' => true]);
        Setting::create(['code' => 'privacypolicy', 'name' => 'Privacy', 'value' => false]);
        Setting::first()->clearCache();
        $request = new UserSignupRequest();
        $messages = $request->messages();
        $this->assertArrayHasKey('terms.accepted', $messages);
        $this->assertStringContainsString('Terms and Conditions', $messages['terms.accepted']);
    }

    public function testMessagesReturnsMessageIfOnlyPrivacySet()
    {
        Setting::create(['code' => 'terms', 'name' => 'Terms', 'value' => false]);
        Setting::create(['code' => 'privacypolicy', 'name' => 'Privacy', 'value' => true]);
        Setting::first()->clearCache();
        $request = new UserSignupRequest();
        $messages = $request->messages();
        $this->assertArrayHasKey('terms.accepted', $messages);
        $this->assertStringContainsString('Privacy Policy', $messages['terms.accepted']);
    }

    public function testMessagesReturnsEmptyArrayIfNeitherSet()
    {
        // no settings created -> fetch should return defaults
        $request = new UserSignupRequest();
        $messages = $request->messages();
        $this->assertEquals([], $messages);
    }

    public function testRulesIncludesTermsIfTermsOrPrivacySet()
    {
        Setting::create(['code' => 'terms', 'name' => 'Terms', 'value' => true]);
        Setting::create(['code' => 'privacypolicy', 'name' => 'Privacy', 'value' => false]);
        Setting::first()->clearCache();
        $user = User::factory()->create();
        $this->be($user);
        $request = new UserSignupRequest();
        $request->setUserResolver(fn() => $user);
        $rules = $request->rules();
        $this->assertArrayHasKey('terms', $rules);
    }

    public function testRulesDoesNotIncludeTermsIfNeitherSet()
    {
        // ensure fetch returns false for both
        // do not create settings
        $user = User::factory()->create();
        $this->be($user);
        $request = new UserSignupRequest();
        $request->setUserResolver(fn() => $user);
        $rules = $request->rules();
        $this->assertArrayNotHasKey('terms', $rules);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
