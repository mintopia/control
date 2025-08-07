<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\HomeController;

class HomeControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new HomeController();
        $this->assertInstanceOf(HomeController::class, $controller);
    }

    public function testDashboardFailsForStaticUserOrEvent()
    {
        $this->fail('Static method mocking for User::count(), User::where(), or Event::where() is not supported in this environment.');
    }

    public function testUnimpersonateFailsForStaticUserOrResponse()
    {
        $this->fail('Static method mocking for User::find(), response(), or Route facade is not supported in this environment.');
    }
}
