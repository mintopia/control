<?php

namespace Tests\Unit\app\Http\Requests;

use Tests\TestCase;
use App\Http\Requests\ClanMembershipRequest;

class ClanMembershipRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new ClanMembershipRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesReturnsArray()
    {
        $request = new ClanMembershipRequest();
        $this->assertIsArray($request->rules());
    }
}
