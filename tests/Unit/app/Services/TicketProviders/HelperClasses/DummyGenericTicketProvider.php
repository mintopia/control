<?php

namespace Tests\Unit\app\Services\TicketProviders\HelperClasses;

use App\Models\TicketProvider;
use App\Models\User;
use App\Services\TicketProviders\GenericTicketProvider;
use GuzzleHttp\Client;

/**
 * Test helper exposing protected methods of GenericTicketProvider as public wrappers.
 */
class DummyGenericTicketProvider extends GenericTicketProvider
{
    public ?TicketProvider $provider = null;

    public function __construct(?TicketProvider $p = null)
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

    public function makeTicketPublic(?User $user, object $data)
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
