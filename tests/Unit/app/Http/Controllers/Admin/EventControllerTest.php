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

    public function testIndexFailsForStaticEventQuery()
    {
        $this->fail('Static method mocking for Event::query() is not supported in this environment.');
    }
}
