<?php

namespace App\Observers;

use App\Models\Ticket;

class TicketObserver
{
    public function saved(Ticket $ticket): void
    {
        if ($ticket->seat_id && $ticket->isDirty(['seat_id', 'user_id', 'ticket_type_id'])) {
            $ticket->seat->plan->updateRevision();
        }
    }
    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "deleted" event.
     */
    public function deleted(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "restored" event.
     */
    public function restored(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "force deleted" event.
     */
    public function forceDeleted(Ticket $ticket): void
    {
        //
    }
}
