<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\EventUpdateRequest;

class EventUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new EventUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsName()
    {
        $request = new EventUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
    }
}
