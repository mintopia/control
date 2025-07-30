<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\TicketImportRequest;

class TicketImportRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketImportRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsCsv()
    {
        $request = new TicketImportRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('csv', $rules);
    }
}
