<?php

namespace Tests\Unit\app\Http\Controllers\Api\V1;

use Tests\TestCase;
use App\Http\Controllers\Api\V1\SeatingPlanController;

class SeatingPlanControllerTest extends TestCase
{
    public function testCanInstantiateController()
    {
        $controller = new SeatingPlanController();
        $this->assertInstanceOf(SeatingPlanController::class, $controller);
    }

    public function testIndexFailsForStaticFractal()
    {
        $this->fail('Static method mocking for fractal() is not supported in this environment.');
    }

    public function testShowFailsForStaticFractal()
    {
        $this->fail('Static method mocking for fractal() is not supported in this environment.');
    }
}
