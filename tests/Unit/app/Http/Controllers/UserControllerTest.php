<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\UserController;

class UserControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new UserController();
        $this->assertInstanceOf(UserController::class, $controller);
    }
}
