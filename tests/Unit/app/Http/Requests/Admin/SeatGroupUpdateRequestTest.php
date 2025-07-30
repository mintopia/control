<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\SeatGroupUpdateRequest;

class SeatGroupUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new SeatGroupUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsName()
    {
        $request = new SeatGroupUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
    }
}
