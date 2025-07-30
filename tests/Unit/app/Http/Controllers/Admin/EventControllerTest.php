<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\EventController;

class EventControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new EventController();
        $this->assertInstanceOf(EventController::class, $controller);
    }
}
