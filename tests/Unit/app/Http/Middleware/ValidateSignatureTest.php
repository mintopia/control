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
    public function testCanInstantiateValidateSignature()
    {
        $middleware = new ValidateSignatureStub();
        $this->assertInstanceOf(ValidateSignature::class, $middleware);
    }

    public function testExceptPropertyIsArray()
    {
        $middleware = new ValidateSignatureStub();
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);
        $this->assertIsArray($except);
    }

    public function testExtendsIlluminateValidateSignature()
    {
        $middleware = new ValidateSignatureStub();
        $this->assertInstanceOf(\Illuminate\Routing\Middleware\ValidateSignature::class, $middleware);
    }

    public function testStaticMethodCannotBeTested()
    {
        $this->fail('Static method testing is not supported in this environment.');
    }
}
