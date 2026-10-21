<?php

namespace Tests\Unit\app\Jobs\HelperClasses;

use App\Models\EmailAddress;
use App\Models\TicketProvider;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Console\OutputStyle;
use Illuminate\Http\Request;

class TestTicketProviderImplementation implements TicketProviderContract
{
    private $provider;
    private $callsRef;

    public function __construct(?TicketProvider $provider = null, &$callsRef = null)
    {
        $this->provider = $provider;
        $this->callsRef = &$callsRef;
    }

    public function __toString()
    {
        return 'TestTicketProvider';
    }

    public function configMapping(): array
    {
        return [];
    }

    public function install(): TicketProvider
    {
        return $this->provider;
    }

    public function processWebhook(Request $request): bool
    {
        return true;
    }

    public function syncTickets(string|EmailAddress $email): void
    {
        if ($this->callsRef === null) {
            $this->callsRef = 0;
        }
        $this->callsRef++;
    }

    public function getEvents(): array
    {
        return [];
    }

    public function getTicketTypes(string $eventExternalId): array
    {
        return [];
    }

    public function syncAllTickets(?OutputStyle $output): void
    {
        // no-op for tests
    }
}
