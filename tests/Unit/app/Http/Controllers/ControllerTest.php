<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\Controller;

class ControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new Controller();
        $this->assertInstanceOf(Controller::class, $controller);
    }
}
