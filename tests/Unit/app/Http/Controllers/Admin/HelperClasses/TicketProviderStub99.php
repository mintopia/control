<?php

namespace Tests\Unit\app\Http\Controllers\Admin\HelperClasses;

use App\Models\EmailAddress;
use App\Models\TicketProvider;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Console\OutputStyle;
use Illuminate\Http\Request;

class TicketProviderStub99 implements TicketProviderContract
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
        return true;
    }

    public function syncTickets(string|EmailAddress $email): void
    {
    }

    public function getEvents(): array
    {
        return ['EV1' => 'E1'];
    }

    public function getTicketTypes(string $eventExternalId): array
    {
        return ['99' => 'New Name'];
    }

    public function syncAllTickets(?OutputStyle $output): void
    {
    }
}
