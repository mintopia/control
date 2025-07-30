<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\ClanMembershipUpdateRequest;

class ClanMembershipUpdateRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new ClanMembershipUpdateRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new ClanMembershipUpdateRequest();
        $this->assertIsArray($request->rules());
    }
}
