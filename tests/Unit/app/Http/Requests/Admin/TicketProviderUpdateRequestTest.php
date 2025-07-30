<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\TicketProviderUpdateRequest;

class TicketProviderUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketProviderUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesIsArray()
    {
        $request = new TicketProviderUpdateRequest();
        $rules = $request->rules();
        $this->assertIsArray($rules);
    }
}
