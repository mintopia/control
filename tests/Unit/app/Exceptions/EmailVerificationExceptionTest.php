<?php

namespace Tests\Unit\app\Exceptions;

use App\Exceptions\EmailVerificationException;
use Exception;
use Tests\TestCase;

class EmailVerificationExceptionTest extends TestCase
{
    public function testCanInstantiateException()
    {
        $exception = new EmailVerificationException('Test message');
        $this->assertInstanceOf(EmailVerificationException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testCanSetCustomCode()
    {
        $exception = new EmailVerificationException('Test message', 123);
        $this->assertEquals(123, $exception->getCode());
    }

    public function testCanSetPreviousException()
    {
        $previous = new Exception('Previous');
        $exception = new EmailVerificationException('Test message', 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testDefaultValues()
    {
        $exception = new EmailVerificationException();
        $this->assertSame('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
