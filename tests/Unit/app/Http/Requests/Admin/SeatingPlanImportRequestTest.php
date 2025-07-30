<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\SeatingPlanImportRequest;

class SeatingPlanImportRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new SeatingPlanImportRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsCsv()
    {
        $request = new SeatingPlanImportRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('csv', $rules);
    }
}
