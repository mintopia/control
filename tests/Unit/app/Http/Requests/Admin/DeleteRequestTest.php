<?php

namespace Tests\Unit\app\Http\Requests\Admin;

use Tests\TestCase;
use App\Http\Requests\Admin\DeleteRequest;

class DeleteRequestTest extends TestCase
{
    public function testAuthorizeReturnsTrue()
    {
        $request = new DeleteRequest();
        $this->assertTrue($request->authorize());
    }

    public function testRulesContainsConfirm()
    {
        $request = new DeleteRequest();
        $rules = $request->rules();
        $this->assertArrayHasKey('confirm', $rules);
    }
}
