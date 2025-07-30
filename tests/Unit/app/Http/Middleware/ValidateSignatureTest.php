<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\ValidateSignature;

class ValidateSignatureTest extends TestCase
{
    public function testCanInstantiateValidateSignature()
    {
        $middleware = new ValidateSignature();
        $this->assertInstanceOf(ValidateSignature::class, $middleware);
    }
}
