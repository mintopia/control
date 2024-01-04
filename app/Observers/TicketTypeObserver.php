<?php

namespace App\Observers;

use App\Models\TicketType;

class TicketTypeObserver
{
    public function saving(TicketType $tickettype): void
    {
        if ($tickettype->isDirty('discord_role_id') || ($tickettype->discord_role_id && !$tickettype->discord_role_name)) {
            $tickettype->updateDiscordRoleName();
        }
    }

    public function saved(TicketType $tickettype): void
    {
        if ($tickettype->isDirty('discord_role_id')) {
            $tickettype->syncDiscordRoles();
        }
    }
}
