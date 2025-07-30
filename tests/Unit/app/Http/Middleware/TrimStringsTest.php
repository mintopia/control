<?php

namespace Tests\Unit\app\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\TrimStrings;

class TrimStringsTest extends TestCase
{
    public function testCanInstantiateTrimStrings()
    {
        $middleware = new TrimStrings();
        $this->assertInstanceOf(TrimStrings::class, $middleware);
    }
}
