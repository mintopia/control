<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\TicketController;

class TicketControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new TicketController();
        $this->assertInstanceOf(TicketController::class, $controller);
    }
}
