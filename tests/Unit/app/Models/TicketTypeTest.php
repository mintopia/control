<?php

namespace Tests\Unit\app\Models;

use Tests\TestCase;
use App\Models\TicketType;

class TicketTypeTest extends TestCase
{
    public function testCanInstantiateTicketType()
    {
        $type = new TicketType();
        $this->assertInstanceOf(TicketType::class, $type);
    }

    public function testEventRelationship()
    {
        $type = new TicketType();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $type->event());
    }

    public function testMappingsRelationship()
    {
        $type = new TicketType();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $type->mappings());
    }

    public function testTicketsRelationship()
    {
        $type = new TicketType();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $type->tickets());
    }

    public function testUpdateDiscordRoleNameSetsNameIfRoleExists()
    {
        $type = new TicketType();
        $type->discord_role_id = '123';
        $mockApi = $this->getMockBuilder(\App\Services\DiscordApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRoles'])
            ->getMock();
        $mockApi->method('getRoles')->willReturn(['123' => 'Test Role']);
        app()->instance(\App\Services\DiscordApi::class, $mockApi);
        $type->updateDiscordRoleName();
        $this->assertEquals('Test Role', $type->discord_role_name);
    }

    public function testUpdateDiscordRoleNameSetsNullIfRoleNotExists()
    {
        $type = new TicketType();
        $type->discord_role_id = '999';
        $mockApi = $this->getMockBuilder(\App\Services\DiscordApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRoles'])
            ->getMock();
        $mockApi->method('getRoles')->willReturn(['123' => 'Test Role']);
        app()->instance(\App\Services\DiscordApi::class, $mockApi);
        $type->updateDiscordRoleName();
        $this->assertNull($type->discord_role_name);
        $this->assertNull($type->discord_role_id);
    }

    public function testUpdateDiscordRoleNameSetsNullIfNoRoleId()
    {
        $type = new TicketType();
        $type->discord_role_id = null;
        $type->discord_role_name = 'Should be cleared';
        $type->updateDiscordRoleName();
        $this->assertNull($type->discord_role_name);
    }

    public function testSyncDiscordRolesQueuesArtisanCommand()
    {
        \Illuminate\Support\Facades\Artisan::shouldReceive('queue')
            ->once()
            ->with('control:sync-discord-roles');
        $type = new TicketType();
        $type->syncDiscordRoles();
    }
}
