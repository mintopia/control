<?php

namespace Tests\Unit\app\Services\TicketProviders;

use App\Models\User;
use App\Models\Ticket;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Services\TicketProviders\TicketTailorProvider;

/**
 * Test helper exposing protected methods of TicketTailorProvider as public wrappers.
 */
class DummyTicketTailorProvider extends TicketTailorProvider
{
    public function __construct(?\App\Models\TicketProvider $provider = null)
    {
        parent::__construct($provider);
    }

    public function verifyWebhookPublic(Request $request): bool
    {
        return $this->verifyWebhook($request);
    }

    public function processTicketPublic(object $data): ?Ticket
    {
        return $this->processTicket($data);
    }

    public function makeTicketPublic(?User $user, object $data): ?Ticket
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

    public function getClientPublic(): Client
    {
        return $this->getClient();
    }

    public function getTicketsPublic(?string $address = null): array
    {
        return $this->getTickets($address);
    }
}
