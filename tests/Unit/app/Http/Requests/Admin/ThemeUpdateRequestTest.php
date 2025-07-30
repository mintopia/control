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
}
