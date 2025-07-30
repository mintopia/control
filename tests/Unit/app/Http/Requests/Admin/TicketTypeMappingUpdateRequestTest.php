<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\TicketTypeMappingUpdateRequest;

class TicketTypeMappingUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketTypeMappingUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsExternalId()
    {
        $request = new TicketTypeMappingUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('external_id', $rules);
    }
}
