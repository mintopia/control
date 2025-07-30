<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\TicketTypeUpdateRequest;

class TicketTypeUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketTypeUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsName()
    {
        $request = new TicketTypeUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
    }
}
