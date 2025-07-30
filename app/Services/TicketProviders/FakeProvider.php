<?php

namespace App\Services\TicketProviders;

use App\Models\TicketProvider;
use Symfony\Component\Console\Output\OutputInterface;

class FakeProvider
{
    public function __construct(protected TicketProvider $provider) {}

    public function syncAllTickets($output = null)
    {
        if ($output instanceof OutputInterface) {
            $output->writeln('FakeProvider: syncAllTickets called');
        }
    }
}
