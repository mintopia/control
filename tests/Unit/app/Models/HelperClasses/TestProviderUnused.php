<?php

namespace Tests\Unit\app\Models\HelperClasses;

use App\Models\EmailAddress;
use App\Models\TicketProvider;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Console\OutputStyle;
use Illuminate\Http\Request;

class TestProviderUnused implements TicketProviderContract
{
    public function __construct(?TicketProvider $provider = null)
    {
    }

    public function configMapping(): array
    {
        return [];
    }

    public function install(): TicketProvider
    {
        return new TicketProvider();
    }

    public function processWebhook(Request $request): bool
    {
        return false;
    }

    public function syncTickets(string|EmailAddress $email): void
    {
    }

    public function getEvents(): array
    {
        return ['1' => 'E1'];
    }

    public function getTicketTypes(string $eventExternalId): array
    {
        return [];
    }

    public function syncAllTickets(?OutputStyle $output): void
    {
    }
}
