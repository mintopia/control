<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\VerifyCsrfToken;

class VerifyCsrfTokenTest extends TestCase
{
    public function testCanInstantiateVerifyCsrfToken()
    {
        $middleware = new VerifyCsrfToken();
        $this->assertInstanceOf(VerifyCsrfToken::class, $middleware);
    }
}
