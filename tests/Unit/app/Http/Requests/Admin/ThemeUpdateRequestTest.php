<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\ThemeUpdateRequest;

class ThemeUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new ThemeUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesIsArray()
    {
        $request = new ThemeUpdateRequest();
        $rules = $request->rules();
        $this->assertIsArray($rules);
    }

    public function testRulesIncludeActiveAndDarkMode()
    {
        $request = new ThemeUpdateRequestStub();
        $request->theme = null;
        $rules = $request->rules();
        $this->assertArrayHasKey('active', $rules);
        $this->assertStringContainsString('bool', $rules['active']);
        $this->assertArrayHasKey('dark_mode', $rules);
        $this->assertStringContainsString('bool', $rules['dark_mode']);
    }

    public function testRulesIncludeAllFieldsIfThemeNotReadonly()
    {
        $request = new ThemeUpdateRequestStub();
        $request->theme = (object)['readonly' => false];
        $rules = $request->rules();
        $expected = [
            'name',
            'css',
            'primary',
            'nav_background',
            'seat_available',
            'seat_disabled',
            'seat_taken',
            'seat_clan',
            'seat_selected'
        ];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $rules);
        }
    }

    public function testRulesOmitsFieldsIfThemeReadonly()
    {
        $request = new ThemeUpdateRequestStub();
        $request->theme = (object)['readonly' => true];
        $rules = $request->rules();
        $this->assertArrayNotHasKey('name', $rules);
        $this->assertArrayNotHasKey('primary', $rules);
        $this->assertArrayNotHasKey('nav_background', $rules);
    }
}

class ThemeUpdateRequestStub extends ThemeUpdateRequest
{
    public $theme = null;
}
