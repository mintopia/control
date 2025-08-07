<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\ClanController;

class ClanControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new ClanController();
        $this->assertInstanceOf(ClanController::class, $controller);
    }

    public function testIndexFailsForStaticClanQuery()
    {
        $this->fail('Static method mocking for Clan::query() is not supported in this environment.');
    }
}
