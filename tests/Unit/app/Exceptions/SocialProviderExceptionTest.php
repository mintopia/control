<?php

namespace Tests\Unit\app\Exceptions;

use Tests\TestCase;
use App\Exceptions\SocialProviderException;

class SocialProviderExceptionTest extends TestCase
{
    public function testCanInstantiateException()
    {
        $exception = new SocialProviderException('Test message');
        $this->assertInstanceOf(SocialProviderException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
}
