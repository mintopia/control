<?php

namespace App\Policies;

use App\Models\Seat;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SeatPolicy
{
    public function pick(User $user, Seat $seat)
    {
        if (!$seat->canPick()) {
            return false;
        }
        $tickets = $user->getPickableTickets($seat->plan->event);
        if (!$tickets) {
            return false;
        }
        if ($seat->ticket) {
            foreach ($tickets as $ticket) {
                if ($ticket->id === $seat->ticket->id) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }
}
