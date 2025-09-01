<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\EmailAddressRequest;

class EmailAddressRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new EmailAddressRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new EmailAddressRequest();
        $this->assertIsArray($request->rules());
    }
}
