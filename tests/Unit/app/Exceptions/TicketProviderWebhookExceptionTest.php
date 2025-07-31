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

    public function testCanSetCustomCode()
    {
        $exception = new TicketProviderWebhookException('Test message', 789);
        $this->assertEquals(789, $exception->getCode());
    }

    public function testCanSetPreviousException()
    {
        $previous = new \Exception('Previous');
        $exception = new TicketProviderWebhookException('Test message', 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }

    // CHECK Function is not implemented yet, test deactivated
    // public function testDefaultValues()
    // {
    //     $exception = new TicketProviderWebhookException();
    //     $this->assertNull($exception->getMessage());
    //     $this->assertEquals(0, $exception->getCode());
    //     $this->assertNull($exception->getPrevious());
    // }
}
