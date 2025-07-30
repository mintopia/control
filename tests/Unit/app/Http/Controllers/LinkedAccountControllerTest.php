<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\LinkedAccountController;

class LinkedAccountControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new LinkedAccountController();
        $this->assertInstanceOf(LinkedAccountController::class, $controller);
    }
}
