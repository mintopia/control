<?php

namespace Tests\Unit\app\Http\Controllers\Admin\HelperClasses;

use App\Models\EmailAddress;
use App\Models\TicketProvider;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Console\OutputStyle;
use Illuminate\Http\Request;

class TestProviderWithEvents implements TicketProviderContract
{
    protected ?TicketProvider $provider;

    public function __construct(?TicketProvider $provider = null)
    {
        $this->provider = $provider;
    }

    public function configMapping(): array
    {
        return [];
    }

    public function install(): TicketProvider
    {
        return $this->provider ?? new TicketProvider();
    }

    public function processWebhook(Request $request): bool
    {
        return false;
    }

    public function syncTickets(string|EmailAddress $email): void
    {
        // noop for tests
    }

    public function getEvents(): array
    {
        // return an array keyed by external id so the controller foreach can find a match
        return ['54321' => 'Provider Event Name', '99999' => 'Other Event'];
    }

    public function getTicketTypes(string $eventExternalId): array
    {
        return [];
    }

    public function syncAllTickets(?OutputStyle $output): void
    {
        // noop
    }
}
