<?php

namespace Tests\Unit\app\Http\Controllers\Admin;

use Tests\TestCase;
use App\Http\Controllers\Admin\EventTicketProviderController;

class EventTicketProviderControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new EventTicketProviderController();
        $this->assertInstanceOf(EventTicketProviderController::class, $controller);
    }

    public function testNoMethodsToTest()
    {
        $this->assertTrue(true, 'No methods to test in EventTicketProviderController.');
    }
}
