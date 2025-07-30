<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\TicketUpdateRequest;

class TicketUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsReference()
    {
        $request = new TicketUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('reference', $rules);
    }
}
