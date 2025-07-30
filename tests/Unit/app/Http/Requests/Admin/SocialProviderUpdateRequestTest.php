<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\SocialProviderUpdateRequest;

class SocialProviderUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new SocialProviderUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesIsArray()
    {
        $request = new SocialProviderUpdateRequest();
        $rules = $request->rules();
        $this->assertIsArray($rules);
    }
}
