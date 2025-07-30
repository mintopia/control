<?php

namespace Tests\Unit\app\Exceptions;

use Tests\TestCase;
use App\Exceptions\Handler;

class HandlerTest extends TestCase
{
    public function testCanInstantiateHandler()
    {
        $handler = new Handler(app());
        $this->assertInstanceOf(Handler::class, $handler);
    }
}
