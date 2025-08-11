<?php

namespace Tests\Feature\app\Console\Commands;

use Tests\TestCase;
use App\Console\Commands\SyncDiscordRoles;
use App\Models\User;
use App\Models\SocialProvider;
use App\Models\LinkedAccount;
use App\Models\TicketType;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SyncDiscordRolesTest extends TestCase
{
    use RefreshDatabase;

    // TODO Tests do not work with the current setup, need to fix
    public function testCanInstantiateCommand()
    {
        $command = new SyncDiscordRoles();
        $this->assertInstanceOf(SyncDiscordRoles::class, $command);
    }

    public function testHandleLogsIfNoDiscordApi()
    {
        Log::shouldReceive('debug')->once()->withArgs([
            fn($msg) => str_contains($msg, 'no API access')
        ]);
        $command = new SyncDiscordRoles();
        $command->handle(null); // No DiscordApi injected
    }

    public function testHandleLogsIfNoProvider()
    {
        Log::shouldReceive('debug')->once()->withArgs([
            fn($msg) => str_contains($msg, 'Social Provider was not found')
        ]);
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
        $user->tickets()->create(['type_id' => $ticketType->id]);

        $mockApi = $this->getMockBuilder(\App\Services\DiscordApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMemberRoles', 'addRoleToMember', 'removeRoleFromMember'])
            ->getMock();
        $mockApi->expects($this->once())->method('getMemberRoles')->willReturn([
            '12345' => (object)['id' => '12345', 'nickname' => 'test', 'roles' => []],
        ]);
        $mockApi->expects($this->once())->method('addRoleToMember');
        $mockApi->expects($this->never())->method('removeRoleFromMember');

        $command = new SyncDiscordRoles();
        $command->handle($mockApi);
    }
}
