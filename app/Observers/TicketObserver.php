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
            if ($ticket->user) {
                // Update new user
                $ticket->user->syncDiscordRoles();
            }
        }
    }
}
