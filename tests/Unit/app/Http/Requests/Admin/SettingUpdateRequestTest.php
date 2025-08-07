<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\SettingUpdateRequest;

class SettingUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new SettingUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesIsArray()
    {
        $request = new SettingUpdateRequest();
        $rules = $request->rules();
        $this->assertIsArray($rules);
    }

    public function testRulesReturnsCorrectKeysAndValuesFromSettings()
    {
        $mockSetting1 = (object)['code' => 'site_name', 'validation' => 'required|string|max:255'];
        $mockSetting2 = (object)['code' => 'site_mode', 'validation' => 'required|in:live,maintenance'];
        $mockSetting3 = (object)['code' => 'no_validation', 'validation' => null];
        \App\Models\Setting::shouldReceive('all')->andReturn([$mockSetting1, $mockSetting2, $mockSetting3]);
        $request = new SettingUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('site_name', $rules);
        $this->assertEquals('required|string|max:255', $rules['site_name']);
        $this->assertArrayHasKey('site_mode', $rules);
        $this->assertEquals('required|in:live,maintenance', $rules['site_mode']);
        $this->assertArrayNotHasKey('no_validation', $rules);
    }
}
