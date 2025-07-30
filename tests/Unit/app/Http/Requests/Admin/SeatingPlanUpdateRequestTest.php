<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\SeatingPlanUpdateRequest;

class SeatingPlanUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new SeatingPlanUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsName()
    {
        $request = new SeatingPlanUpdateRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('name', $rules);
    }
}
