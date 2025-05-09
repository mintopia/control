<?php

namespace App\Models\Helpers;

use App\Models\Event;
use App\Models\Seat;
use App\Models\TicketType;
use App\Models\User;

class TicketImport
{
    public function __construct(
        public User $user,
        public Event $event,
        public TicketType $type,
        public ?Seat $seat = null,
    ) {
    }
}
