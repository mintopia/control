<?php

namespace Tests\Unit\app\Services\Contracts\HelperClasses;

use App\Models\EmailAddress;
use App\Models\TicketProvider;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Console\OutputStyle;
use Illuminate\Http\Request;

class DummyTicketProvider implements TicketProviderContract
{
    public function __construct(?TicketProvider $provider = null)
    {
    }

    public function configMapping(): array
    {
        return [
            'apikey' => [
                'name' => 'API Key',
                'validation' => 'required|string',
                'value' => 'dummy-key',
            ],
        ];
    }

    public function install(): TicketProvider
    {
        return new TicketProvider(['name' => 'Dummy', 'code' => 'dummy']);
    }

    public function processWebhook(Request $request): bool
    {
        return true;
    }

    public function syncTickets(string|EmailAddress $email): void
    {
        // Dummy implementation
    }

    public function getEvents(): array
    {
        return ['evt1' => 'Event 1', 'evt2' => 'Event 2'];
    }

    public function getTicketTypes(string $eventExternalId): array
    {
        return ['type1' => 'VIP', 'type2' => 'Standard'];
    }

    public function syncAllTickets(?OutputStyle $output): void
    {
        // Dummy implementation
    }
}
