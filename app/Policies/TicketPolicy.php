<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    public function update(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id;
    }
}
