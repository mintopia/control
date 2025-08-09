<?php

namespace Tests\Unit\app\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\ClanController;

class ClanControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new ClanController();
        $this->assertInstanceOf(ClanController::class, $controller);
    }

    //TODO Add more tests
}
