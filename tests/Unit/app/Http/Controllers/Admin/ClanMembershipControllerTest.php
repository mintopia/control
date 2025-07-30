<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\ClanMembershipController;

class ClanMembershipControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new ClanMembershipController();
        $this->assertInstanceOf(ClanMembershipController::class, $controller);
    }
}
