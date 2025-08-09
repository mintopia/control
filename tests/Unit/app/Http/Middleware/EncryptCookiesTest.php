<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\EncryptCookies;

class EncryptCookiesStub extends EncryptCookies
{
    public function __construct() {}
}

class EncryptCookiesTest extends TestCase
{
    public function testCanInstantiateEncryptCookies()
    {
        $middleware = new EncryptCookiesStub();
        $this->assertInstanceOf(EncryptCookies::class, $middleware);
    }

    public function testExceptPropertyIsArray()
    {
        $middleware = new EncryptCookiesStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);
        $this->assertIsArray($except);
    }

    public function testExtendsIlluminateEncryptCookies()
    {
        $middleware = new EncryptCookiesStub();
        $this->assertInstanceOf(\Illuminate\Cookie\Middleware\EncryptCookies::class, $middleware);
    }

    public function testDefaultExceptPropertyIsEmpty()
    {
        $middleware = new EncryptCookiesStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);
        $this->assertEmpty($except);
    }

    public function testCustomExceptPropertyInSubclass()
    {
        $middleware = new class extends EncryptCookies {
            protected $except = ['foo_cookie', 'bar_cookie'];
            public function __construct() {}
        };
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);
        $this->assertEquals(['foo_cookie', 'bar_cookie'], $except);
    }

}
