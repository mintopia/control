<?php

namespace Tests\Unit\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Tests\TestCase;

class ControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new Controller();
        $this->assertInstanceOf(Controller::class, $controller);
    }

    public function testControllerProvidesAuthorizationAndValidationHelpers()
    {
        $controller = new Controller();

        // methods provided by AuthorizesRequests and ValidatesRequests traits
        $this->assertTrue(method_exists($controller, 'authorize'));
        $this->assertTrue(method_exists($controller, 'validate'));
    }
}
