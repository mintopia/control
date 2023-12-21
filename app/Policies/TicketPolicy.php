<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function update(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id;
    }

    public function see(User $user, Ticket $ticket): bool
    {
        if (!$ticket->event->draft) {
            return true;
        }
        return $user->hasRole('admin');
    }
}
