<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\LinkedAccountController;

class LinkedAccountControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new LinkedAccountController();
        $this->assertInstanceOf(LinkedAccountController::class, $controller);
    }
}
