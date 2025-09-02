<?php

namespace Tests\Unit\app\Services\TicketProviders;

use App\Models\User;
use App\Models\Ticket;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Services\TicketProviders\WooCommerceProvider;
use App\Models\TicketProvider;

/**
 * Lightweight test helper that exposes protected WooCommerceProvider methods as public
 * so unit tests can call them directly without mocking protected methods.
 */
class DummyWooCommerceProvider extends WooCommerceProvider
{
    /**
     * Constructor must match App\Services\Contracts\TicketProviderContract::__construct
     *
     * @param ?TicketProvider $provider
     */
    public function __construct(?TicketProvider $provider = null)
    {
        $this->provider = $provider;
        parent::__construct($provider);
    }

    public function getTicketsPublic(?string $address = null): array
    {
        return $this->getTickets($address);
    }

    public function processTicketsPublic(array $ticketData, string $address, ?User $user = null): void
    {
        $this->processTickets($ticketData, $address, $user);
    }

    public function makeTicketPublic(?User $user, object $data): ?Ticket
    {
        return $this->makeTicket($user, $data);
    }

    public function parseOrderPublic(object $order): array
    {
        return $this->parseOrder($order);
    }

    public function verifyWebhookPublic(Request $request): bool
    {
        return $this->verifyWebhook($request);
    }

    public function getQrCodePublic(object $data): string
    {
        return $this->getQrCode($data);
    }

    public function getClientPublic(): Client
    {
        return $this->getClient();
    }

    public function getTypePublic(string $externalId)
    {
        return $this->getType($externalId);
    }

    public function getEventsPublic(): array
    {
        return $this->getEvents();
    }

    public function getTicketTypesPublic(string $eventExternalId): array
    {
        return $this->getTicketTypes($eventExternalId);
    }

    // --- Test override hooks ---
    /** @var bool|null If set, used as the return for verifyWebhook */
    public ?bool $forceVerify = null;

    /** @var array|null If set, used as the return value for parseOrder */
    public ?array $parseOverride = null;

    /** @var bool Flag set when processTickets is invoked */
    public bool $processCalled = false;

    /** @var \Closure|null Optional override for processTickets behaviour */
    public $processOverride = null;

    protected function verifyWebhook(Request $request): bool
    {
        if ($this->forceVerify !== null) {
            return $this->forceVerify;
        }
        return parent::verifyWebhook($request);
    }

    protected function parseOrder(object $order): array
    {
        if ($this->parseOverride !== null) {
            return $this->parseOverride;
        }
        return parent::parseOrder($order);
    }

    protected function processTickets(array $ticketData, string $address, ?User $user = null): void
    {
        $this->processCalled = true;
        if ($this->processOverride instanceof \Closure) {
            ($this->processOverride)($ticketData, $address, $user);
            return;
        }
        // avoid calling parent by default to keep tests lightweight
    }
}
