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
        // previous is the fifth parameter in the new constructor signature
        $exception = new TicketProviderWebhookException('Test message', 0, null, null, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testGettersAndStaticFactories()
    {
        // missingHeader factory
        $ex = TicketProviderWebhookException::missingHeader('X-Sig');
        $this->assertInstanceOf(TicketProviderWebhookException::class, $ex);
        $this->assertEquals(TicketProviderWebhookException::MISSING_HEADER, $ex->getReasonCode());
        $this->assertEquals('Missing webhook signature header', $ex->getMessage());
        $this->assertEquals('X-Sig', $ex->getHeader());

        // invalidFormat factory
        $ex2 = TicketProviderWebhookException::invalidFormat('X-Sig');
        $this->assertEquals(TicketProviderWebhookException::INVALID_FORMAT, $ex2->getReasonCode());
        $this->assertEquals('Invalid webhook signature header format', $ex2->getMessage());

        // timestampTooOld factory
        $ts = 1234567890;
        $ex3 = TicketProviderWebhookException::timestampTooOld($ts);
        $this->assertEquals(TicketProviderWebhookException::TIMESTAMP_TOO_OLD, $ex3->getReasonCode());
        $this->assertEquals($ts, $ex3->getTimestamp());

        // hashMismatch factory
        $ex4 = TicketProviderWebhookException::hashMismatch('X-Sig', $ts);
        $this->assertEquals(TicketProviderWebhookException::HASH_MISMATCH, $ex4->getReasonCode());
        $this->assertEquals('X-Sig', $ex4->getHeader());
        $this->assertEquals($ts, $ex4->getTimestamp());
    }

    public function testDefaultValues()
    {
        $exception = new TicketProviderWebhookException();
        $this->assertSame('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
