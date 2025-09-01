<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\TrimStrings;

class TrimStringsStub extends TrimStrings
{
    public function __construct() {}
}

class TrimStringsTest extends TestCase
{
    public function testCanInstantiateTrimStrings()
    {
        $middleware = new TrimStringsStub();
        $this->assertInstanceOf(TrimStrings::class, $middleware);
    }

    public function testExceptPropertyContainsPasswords()
    {
        $middleware = new TrimStringsStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);
        $this->assertContains('current_password', $except);
        $this->assertContains('password', $except);
        $this->assertContains('password_confirmation', $except);
    }

    public function testExtendsIlluminateTrimStrings()
    {
        $middleware = new TrimStringsStub();
        $this->assertInstanceOf(\Illuminate\Foundation\Http\Middleware\TrimStrings::class, $middleware);
    }

    public function testExceptPropertyIsArray()
    {
        $middleware = new TrimStringsStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);
        $this->assertIsArray($except);
    }

    public function testCustomExceptPropertyInSubclass()
    {
        $middleware = new class extends TrimStrings {
            protected $except = ['foo', 'bar'];
            public function __construct() {}
        };
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);
        $this->assertEquals(['foo', 'bar'], $except);
    }
}
