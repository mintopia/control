<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\EventMappingUpdateRequest;

class EventMappingUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new EventMappingUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsExternalId()
    {
        $request = new EventMappingUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('external_id', $rules);
    }
}
