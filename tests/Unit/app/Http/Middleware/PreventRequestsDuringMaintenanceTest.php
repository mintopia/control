<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\PreventRequestsDuringMaintenance;

class PreventRequestsDuringMaintenanceTest extends TestCase
{
    public function testCanInstantiatePreventRequestsDuringMaintenance()
    {
        $middleware = new PreventRequestsDuringMaintenance();
        $this->assertInstanceOf(PreventRequestsDuringMaintenance::class, $middleware);
    }
}
