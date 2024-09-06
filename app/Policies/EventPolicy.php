<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventPolicy
{
    public function see(User $user, Event $event): bool
    {
        if (!$event->draft) {
            return true;
        }
        return $user->hasAnyRole(['admin', 'manager']);
    }
}
