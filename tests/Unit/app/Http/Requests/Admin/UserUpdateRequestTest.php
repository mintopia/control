<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\UserUpdateRequest;

class UserUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new UserUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsNickname()
    {
        $request = new UserUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('nickname', $rules);
    }
}
