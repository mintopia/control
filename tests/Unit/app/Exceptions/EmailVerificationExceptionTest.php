<?php

namespace Tests\Unit\app\Exceptions;

use Tests\TestCase;
use App\Exceptions\EmailVerificationException;

class EmailVerificationExceptionTest extends TestCase
{
    public function testCanInstantiateException()
    {
        $exception = new EmailVerificationException('Test message');
        $this->assertInstanceOf(EmailVerificationException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
}
