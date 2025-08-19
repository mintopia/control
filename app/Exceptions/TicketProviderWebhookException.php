<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class TicketProviderWebhookException extends Exception
{
    public const MISSING_HEADER = 1;
    public const INVALID_FORMAT = 2;
    public const TIMESTAMP_TOO_OLD = 3;
    public const HASH_MISMATCH = 4;

    protected int $reasonCode = 0;
    protected ?string $header = null;
    protected ?int $timestamp = null;

    public function __construct(string $message = "", int $reasonCode = 0, ?string $header = null, ?int $timestamp = null, ?Throwable $previous = null)
    {
        parent::__construct($message, $reasonCode, $previous);
        $this->reasonCode = $reasonCode;
        $this->header = $header;
        $this->timestamp = $timestamp;
    }

    public static function missingHeader(?string $header = null): self
    {
        return new self('Missing webhook signature header', self::MISSING_HEADER, $header, null);
    }

    public static function invalidFormat(?string $header = null): self
    {
        return new self('Invalid webhook signature header format', self::INVALID_FORMAT, $header, null);
    }

    public static function timestampTooOld(?int $timestamp = null): self
    {
        return new self('Webhook message is more than allowed age', self::TIMESTAMP_TOO_OLD, null, $timestamp);
    }

    public static function hashMismatch(?string $header = null, ?int $timestamp = null): self
    {
        return new self('Hash does not match signature', self::HASH_MISMATCH, $header, $timestamp);
    }

    public function getReasonCode(): int
    {
        return $this->reasonCode;
    }

    public function getHeader(): ?string
    {
        return $this->header;
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }
}
