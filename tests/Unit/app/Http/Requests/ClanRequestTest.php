<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\ClanRequest;

class ClanRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new ClanRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new ClanRequest();
        $this->assertIsArray($request->rules());
    }
}
