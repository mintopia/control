<?php

namespace Tests\Unit\app\Observers;

use App\Models\TicketType;
use App\Observers\TicketTypeObserver;
use Tests\TestCase;

class TicketTypeObserverTest extends TestCase
{
    public function testSavingUpdatesDiscordRoleNameIfDirtyOrMissing()
    {
        $ticketType = $this->getMockBuilder(TicketType::class)->onlyMethods(['isDirty', 'updateDiscordRoleName'])->getMock();
        $ticketType->expects($this->once())->method('isDirty')->with('discord_role_id')->willReturn(true);
        $ticketType->expects($this->once())->method('updateDiscordRoleName');
        $observer = new TicketTypeObserver();
        $observer->saving($ticketType);
    }

    public function testSavedSyncsDiscordRolesIfDirty()
    {
        $ticketType = $this->getMockBuilder(TicketType::class)->onlyMethods(['isDirty', 'syncDiscordRoles'])->getMock();
        $ticketType->expects($this->once())->method('isDirty')->with('discord_role_id')->willReturn(true);
        $ticketType->expects($this->once())->method('syncDiscordRoles');
        $observer = new TicketTypeObserver();
        $observer->saved($ticketType);
    }
}
