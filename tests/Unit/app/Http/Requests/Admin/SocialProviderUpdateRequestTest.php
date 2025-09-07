<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;

class SocialProviderUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new HelperClasses\SocialProviderUpdateRequestStub();
        $request->provider = (object)['can_be_renamed' => false, 'settings' => []];
        $this->assertTrue($request->authorize());
    }

    public function testRulesIsArray()
    {
        $request = new HelperClasses\SocialProviderUpdateRequestStub();
        $request->provider = (object)['can_be_renamed' => false, 'settings' => []];
        $rules = $request->rules();
        $this->assertIsArray($rules);
    }

    public function testRulesIncludeEnabledAndAuthEnabled()
    {
        $request = new HelperClasses\SocialProviderUpdateRequestStub();
        $request->provider = (object)['can_be_renamed' => false, 'settings' => []];
        $rules = $request->rules();
        $this->assertArrayHasKey('enabled', $rules);
        $this->assertStringContainsString('boolean', $rules['enabled']);
        $this->assertArrayHasKey('auth_enabled', $rules);
        $this->assertStringContainsString('boolean', $rules['auth_enabled']);
    }

    public function testRulesIncludeNameIfCanBeRenamed()
    {
        $request = new HelperClasses\SocialProviderUpdateRequestStub();
        $request->provider = (object)['can_be_renamed' => true, 'settings' => []];
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
        $this->assertStringContainsString('string', $rules['name']);
        $this->assertStringContainsString('max:100', $rules['name']);
    }

    public function testRulesIncludeSettingsValidation()
    {
        $mockSetting1 = (object)['code' => 'client_id', 'validation' => 'required|string'];
        $mockSetting2 = (object)['code' => 'client_secret', 'validation' => 'required|string|min:10'];
        $request = new HelperClasses\SocialProviderUpdateRequestStub();
        $request->provider = (object)[
            'can_be_renamed' => false,
            'settings' => [$mockSetting1, $mockSetting2]
        ];
        $rules = $request->rules();
        $this->assertArrayHasKey('client_id', $rules);
        $this->assertEquals('required|string', $rules['client_id']);
        $this->assertArrayHasKey('client_secret', $rules);
        $this->assertEquals('required|string|min:10', $rules['client_secret']);
    }
}
