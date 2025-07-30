<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\EmailVerifyRequest;

class EmailVerifyRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new EmailVerifyRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new EmailVerifyRequest();
        $this->assertIsArray($request->rules());
    }
}
