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
}
