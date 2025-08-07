<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\PreventRequestsDuringMaintenance;

class PreventRequestsDuringMaintenanceStub extends PreventRequestsDuringMaintenance
{
    public function __construct() {}
}

class PreventRequestsDuringMaintenanceTest extends TestCase
{
    public function testCanInstantiatePreventRequestsDuringMaintenance()
    {
        $middleware = new PreventRequestsDuringMaintenanceStub();
        $this->assertInstanceOf(PreventRequestsDuringMaintenance::class, $middleware);
    }

    public function testExceptPropertyIsArray()
    {
        $middleware = new PreventRequestsDuringMaintenanceStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);
        $this->assertIsArray($except);
    }

    public function testExtendsIlluminatePreventRequestsDuringMaintenance()
    {
        $middleware = new PreventRequestsDuringMaintenanceStub();
        $this->assertInstanceOf(\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class, $middleware);
    }

    public function testStaticMethodCannotBeTested()
    {
        $this->fail('Static method testing is not supported in this environment.');
    }
}
