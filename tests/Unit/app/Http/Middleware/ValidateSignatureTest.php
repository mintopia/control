<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\ValidateSignature;

class ValidateSignatureStub extends ValidateSignature
{
    public function __construct() {}
}

class ValidateSignatureTest extends TestCase
{
    public function testDefaultExceptPropertyIsEmpty()
    {
        $middleware = new ValidateSignatureStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);
        $this->assertEmpty($except);
    }

    public function testCustomExceptPropertyInSubclass()
    {
        $middleware = new class extends ValidateSignature {
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
