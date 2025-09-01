<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\TicketTransferRequest;

class TicketTransferRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new TicketTransferRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new TicketTransferRequest();
        $this->assertIsArray($request->rules());
    }
}
