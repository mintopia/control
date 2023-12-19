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

    /**
     * Handle the TicketProvider "created" event.
     */
    public function created(TicketProvider $ticketProvider): void
    {
        //
    }

    /**
     * Handle the TicketProvider "updated" event.
     */
    public function updated(TicketProvider $ticketProvider): void
    {
        //
    }

    /**
     * Handle the TicketProvider "deleted" event.
     */
    public function deleted(TicketProvider $ticketProvider): void
    {
        //
    }

    /**
     * Handle the TicketProvider "restored" event.
     */
    public function restored(TicketProvider $ticketProvider): void
    {
        //
    }

    /**
     * Handle the TicketProvider "force deleted" event.
     */
    public function forceDeleted(TicketProvider $ticketProvider): void
    {
        //
    }
}
