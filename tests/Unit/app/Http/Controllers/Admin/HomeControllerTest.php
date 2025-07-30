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
}
