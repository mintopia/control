<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\EncryptCookies;

class EncryptCookiesTest extends TestCase
{
    public function testCanInstantiateEncryptCookies()
    {
        $middleware = new EncryptCookies();
        $this->assertInstanceOf(EncryptCookies::class, $middleware);
    }
}
