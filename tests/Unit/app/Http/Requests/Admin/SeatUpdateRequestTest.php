<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\SeatUpdateRequest;

class SeatUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new SeatUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsX()
    {
        $request = new SeatUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('x', $rules);
    }
}
