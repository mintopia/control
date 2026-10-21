<?php

namespace Tests\Unit\app\Exceptions;

use App\Exceptions\SocialProviderException;
use Exception;
use Tests\TestCase;

class SocialProviderExceptionTest extends TestCase
{
    public function testCanInstantiateException()
    {
        $exception = new SocialProviderException('Test message');
        $this->assertInstanceOf(SocialProviderException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testCanSetCustomCode()
    {
        $exception = new SocialProviderException('Test message', 456);
        $this->assertEquals(456, $exception->getCode());
    }

    public function testCanSetPreviousException()
    {
        $previous = new Exception('Previous');
        $exception = new SocialProviderException('Test message', 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultValues()
    {
        $exception = new SocialProviderException();
        $this->assertSame('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
