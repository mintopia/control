<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\EventMappingController;

class EventMappingControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new EventMappingController();
        $this->assertInstanceOf(EventMappingController::class, $controller);
    }
}
