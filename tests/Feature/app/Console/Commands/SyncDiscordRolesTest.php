<?php

namespace Tests\Feature\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\SyncDiscordRoles;
use App\Models\User;
use App\Models\SocialProvider;
use App\Models\LinkedAccount;
use App\Models\TicketType;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SyncDiscordRolesTest extends TestCase
{
    use RefreshDatabase;

    public function testCanInstantiateCommand()
    {
        $command = new SyncDiscordRoles();
        $this->assertInstanceOf(SyncDiscordRoles::class, $command);
    }

    public function testHandleLogsIfNoDiscordApi()
    {
        Log::shouldReceive('debug')->once()->withArgs(function ($args) {
            $msg = is_array($args) ? ($args[0] ?? '') : $args;
            return (is_string($msg) || is_numeric($msg)) && strpos((string)$msg, 'no API access') !== false;
        });
        $command = new SyncDiscordRoles();
        $command->handle(null); // No DiscordApi injected
    }

    public function testHandleLogsIfNoProvider()
    {
        Log::shouldReceive('debug')->once()->withArgs(function ($args) {
            $msg = is_array($args) ? ($args[0] ?? '') : $args;
            return (is_string($msg) || is_numeric($msg)) && strpos((string)$msg, 'Social Provider was not found') !== false;
        });
        $mockApi = $this->getMockBuilder(\App\Services\DiscordApi::class)
            ->disableOriginalConstructor()
            ->getMock();
        $command = new SyncDiscordRoles();
        $command->handle($mockApi);
    }

    public function testHandleSyncsRolesForUser()
    {
        $provider = SocialProvider::factory()->create(['code' => 'discord']);
        $user = User::factory()->create();
        $linked = LinkedAccount::factory()->create([
            'user_id' => $user->id,
            'social_provider_id' => $provider->id,
            'external_id' => '12345',
        ]);
        $ticketType = TicketType::factory()->create();
        // create a full Ticket via factory so required fields (ticket_provider_id, event_id, etc.) are populated
        Ticket::factory()->create(['user_id' => $user->id, 'ticket_type_id' => $ticketType->id]);

        $mockApi = $this->getMockBuilder(\App\Services\DiscordApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMemberRoles', 'addRoleToMember', 'removeRoleFromMember'])
            ->getMock();
        $mockApi->expects($this->once())->method('getMemberRoles')->willReturn([
            '12345' => (object)['id' => '12345', 'nickname' => 'test', 'roles' => []],
        ]);
        $mockApi->expects($this->once())->method('addRoleToMember');
        $mockApi->expects($this->never())->method('removeRoleFromMember');

        // Bind the mock into the container so the command resolves it via DI
        $this->app->instance(\App\Services\DiscordApi::class, $mockApi);

        // Run the command through Artisan so Eloquent and container resolution behave as in production
        $this->artisan('control:sync-discord-roles')->assertExitCode(0);
    }
}
