<?php

namespace Tests\Unit\app\Exceptions;

use Tests\TestCase;
use App\Exceptions\TicketProviderWebhookException;

class TicketProviderWebhookExceptionTest extends TestCase
{
    public function testCanInstantiateException()
    {
        $exception = new TicketProviderWebhookException('Test message');
        $this->assertInstanceOf(TicketProviderWebhookException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
}
