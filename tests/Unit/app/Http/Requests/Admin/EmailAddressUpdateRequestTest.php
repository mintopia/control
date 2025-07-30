<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\EmailAddressUpdateRequest;

class EmailAddressUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new EmailAddressUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsAddress()
    {
        $request = new EmailAddressUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('address', $rules);
    }
}
