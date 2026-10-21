<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use App\Http\Requests\Admin\SettingUpdateRequest;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingUpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthorizeReturnsTrue()
    {
        $request = new SettingUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesIsArray()
    {
        // With a fresh database and no settings, rules() should still return an array
        $request = new SettingUpdateRequest();
        $rules = $request->rules();
        $this->assertIsArray($rules);
    }

    public function testRulesReturnsCorrectKeysAndValuesFromSettings()
    {
        // Create real Setting records so the request builds rules from the DB
        Setting::factory()->create(['code' => 'site_name', 'validation' => 'required|string|max:255']);
        Setting::factory()->create(['code' => 'site_mode', 'validation' => 'required|in:live,maintenance']);
        Setting::factory()->create(['code' => 'no_validation', 'validation' => null]);

        $request = new SettingUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('site_name', $rules);
        $this->assertEquals('required|string|max:255', $rules['site_name']);
        $this->assertArrayHasKey('site_mode', $rules);
        $this->assertEquals('required|in:live,maintenance', $rules['site_mode']);
        $this->assertArrayNotHasKey('no_validation', $rules);
    }
}
