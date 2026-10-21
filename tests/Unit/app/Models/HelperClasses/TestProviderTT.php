<?php

namespace Tests\Unit\app\Models\HelperClasses;

use App\Models\EmailAddress;
use App\Models\TicketProvider;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Console\OutputStyle;
use Illuminate\Http\Request;

class TestProviderTT implements TicketProviderContract
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
        return [];
    }

    public function getTicketTypes(string $eventExternalId): array
    {
        return ['t1' => (object)['id' => 't1', 'name' => 'T1', 'used' => false]];
    }

    public function syncAllTickets(?OutputStyle $output): void
    {
    }
}
