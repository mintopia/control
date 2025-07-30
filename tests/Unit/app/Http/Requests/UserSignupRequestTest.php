<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\UserSignupRequest;

class UserSignupRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new UserSignupRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new UserSignupRequest();
        $this->assertIsArray($request->rules());
    }
}
