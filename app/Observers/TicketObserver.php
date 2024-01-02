<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\User;

class TicketObserver
{
    public function saved(Ticket $ticket): void
    {
        if ($ticket->isDirty() && $ticket->seat) {
            $ticket->seat->plan->updateRevision();
        }
        if ($ticket->isDirty('user_id')) {
            $original = $ticket->getOriginal('user_id');
            if ($original) {
                $originalUser = User::whereId($original)->first();
                if ($originalUser) {
                    $originalUser->syncDiscordRoles();
                }
            }
            // Update new user
            $ticket->user->syncDiscordRoles();
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
