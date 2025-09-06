<?php

namespace Tests\Unit\app\Services\TicketProviders;

use GuzzleHttp\Client;
use App\Services\TicketProviders\GenericTicketProvider;

/**
 * Test helper exposing protected methods of GenericTicketProvider as public wrappers.
 */
class DummyGenericTicketProvider extends GenericTicketProvider
{
    public ?\App\Models\TicketProvider $provider = null;
    public function __construct(?\App\Models\TicketProvider $p = null)
    {
        parent::__construct($p);
        $this->provider = $p;
    }

    // expose protected methods for testing convenience
    public function getClientPublic(): Client
    {
        return $this->getClient();
    }

    public function getTicketsPublic(?string $address = null): array
    {
        return $this->getTickets($address);
    }

    public function processTicketPublic(object $data)
    {
        return $this->processTicket($data);
    }

    public function makeTicketPublic(?\App\Models\User $user, object $data)
    {
        return $this->makeTicket($user, $data);
    }

    public function getEventPublic(string $externalId)
    {
        return $this->getEvent($externalId);
    }

    public function getTypePublic(string $externalId)
    {
        return $this->getType($externalId);
    }

    public function getQrCodePublic(object $data): string
    {
        return $this->getQrCode($data);
    }
}
