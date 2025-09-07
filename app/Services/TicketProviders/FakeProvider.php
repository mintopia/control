<?php

namespace App\Services\TicketProviders;

use App\Models\EmailAddress;
use App\Models\TicketProvider;
use App\Services\Contracts\TicketProviderContract;
use Illuminate\Console\OutputStyle;
use Illuminate\Http\Request;
use RuntimeException;

class FakeProvider implements TicketProviderContract
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
        // return existing provider or throw if not available; tests shouldn't call this
        return $this->provider ?? throw new RuntimeException('No provider');
    }

    public function processWebhook(Request $request): bool
    {
        return true;
    }

    public function syncTickets(string|EmailAddress $email): void
    {
        // noop for tests
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
        if ($output instanceof OutputStyle) {
            $output->writeln('FakeProvider: syncAllTickets called');
        }
    }
}
