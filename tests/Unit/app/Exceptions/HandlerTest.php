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

    public function testDontFlashProperty()
    {
        $handler = new Handler(app());
        $reflection = new \ReflectionClass($handler);
        $property = $reflection->getProperty('dontFlash');
        $property->setAccessible(true);
        $dontFlash = $property->getValue($handler);
        $this->assertContains('current_password', $dontFlash);
        $this->assertContains('password', $dontFlash);
        $this->assertContains('password_confirmation', $dontFlash);
    }

    public function testRegisterMethodIsCallable()
    {
        $handler = new Handler(app());
        $this->assertNull($handler->register());
    }
}
