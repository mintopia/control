<?php

namespace App\Observers;

use App\Models\TicketProvider;

class TicketProviderObserver
{
    public function saving(TicketProvider $ticketProvider): void
    {
        if (!$ticketProvider->cache_prefix) {
            $ticketProvider->cache_prefix = time();
        }
    }
}
