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

    public function testHandleWithUserArgumentFiltersAccounts()
    {
        $provider = SocialProvider::factory()->create(['code' => 'discord']);
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // linked accounts for both users
        $linkedA = LinkedAccount::factory()->create([
            'user_id' => $userA->id,
            'social_provider_id' => $provider->id,
            'external_id' => '111',
        ]);
        $linkedB = LinkedAccount::factory()->create([
            'user_id' => $userB->id,
            'social_provider_id' => $provider->id,
            'external_id' => '222',
        ]);

        // Only userA has a ticket that requires a role
        $tt = TicketType::factory()->create(['discord_role_id' => '10']);
        Ticket::factory()->create(['user_id' => $userA->id, 'ticket_type_id' => $tt->id]);

        $mockApi = $this->getMockBuilder(\App\Services\DiscordApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMemberRoles', 'addRoleToMember', 'removeRoleFromMember'])
            ->getMock();

        // Return both members, but command should only process userA when arg provided
        $mockApi->expects($this->once())->method('getMemberRoles')->willReturn([
            '111' => (object)['id' => '111', 'nickname' => 'a', 'roles' => []],
            '222' => (object)['id' => '222', 'nickname' => 'b', 'roles' => []],
        ]);
        $mockApi->expects($this->once())->method('addRoleToMember');
        $mockApi->expects($this->never())->method('removeRoleFromMember');

        $this->app->instance(\App\Services\DiscordApi::class, $mockApi);

        // Call the command for userA specifically
        $this->artisan('control:sync-discord-roles', ['user' => $userA->id])->assertExitCode(0);
    }

    public function testGetManagedRolesReturnsUniqueIds()
    {
        // create multiple ticket types, with duplicates
        TicketType::factory()->create(['discord_role_id' => '100']);
        TicketType::factory()->create(['discord_role_id' => '100']);
        TicketType::factory()->create(['discord_role_id' => '200']);

        $cmd = new SyncDiscordRoles();
        $rm = new \ReflectionMethod(SyncDiscordRoles::class, 'getManagedRoles');
        $rm->setAccessible(true);
        $roles = $rm->invoke($cmd);

        // should contain unique values 100 and 200
        sort($roles);
        $this->assertEquals(['100', '200'], $roles);
    }

    public function testSyncAccountAddsAndRemovesRoles()
    {
        $provider = SocialProvider::factory()->create(['code' => 'discord']);
        $user = User::factory()->create();
        $linked = LinkedAccount::factory()->create([
            'user_id' => $user->id,
            'social_provider_id' => $provider->id,
            'external_id' => '999',
        ]);

        // Managed roles are 300 and 400; user only should have 300
        $ttKeep = TicketType::factory()->create(['discord_role_id' => '300']);
        $ttRemove = TicketType::factory()->create(['discord_role_id' => '400']);

        // User only has ticket for 300 (so shouldHave = [300])
        Ticket::factory()->create(['user_id' => $user->id, 'ticket_type_id' => $ttKeep->id]);

        $mockApi = $this->getMockBuilder(\App\Services\DiscordApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMemberRoles', 'addRoleToMember', 'removeRoleFromMember'])
            ->getMock();

        // discord member currently has role 400 (managed but not desired) so should be removed
        $mockApi->expects($this->once())->method('getMemberRoles')->willReturn([
            '999' => (object)['id' => '999', 'nickname' => 'x', 'roles' => ['400']],
        ]);
        $mockApi->expects($this->once())->method('addRoleToMember')->with('300', '999');
        $mockApi->expects($this->once())->method('removeRoleFromMember')->with('400', '999');

        $this->app->instance(\App\Services\DiscordApi::class, $mockApi);

        $this->artisan('control:sync-discord-roles')->assertExitCode(0);
    }

    public function testHandleWithEmptyDiscordMembersDoesNothing()
    {
        $provider = SocialProvider::factory()->create(['code' => 'discord']);
        $user = User::factory()->create();
        $linked = LinkedAccount::factory()->create([
            'user_id' => $user->id,
            'social_provider_id' => $provider->id,
            'external_id' => 'nope',
        ]);

        $mockApi = $this->getMockBuilder(\App\Services\DiscordApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMemberRoles', 'addRoleToMember', 'removeRoleFromMember'])
            ->getMock();

        $mockApi->expects($this->once())->method('getMemberRoles')->willReturn([]);
        $mockApi->expects($this->never())->method('addRoleToMember');
        $mockApi->expects($this->never())->method('removeRoleFromMember');

        $this->app->instance(\App\Services\DiscordApi::class, $mockApi);

        $this->artisan('control:sync-discord-roles')->assertExitCode(0);
    }

    public function testGetManagedRolesMemoization()
    {
        TicketType::factory()->create(['discord_role_id' => '500']);
        $cmd = new SyncDiscordRoles();
        $rm = new \ReflectionMethod(SyncDiscordRoles::class, 'getManagedRoles');
        $rm->setAccessible(true);
        $roles1 = $rm->invoke($cmd);
        $this->assertNotEmpty($roles1);

        // ensure property now cached
        $prop = new \ReflectionProperty(SyncDiscordRoles::class, 'managedRoles');
        $prop->setAccessible(true);
        $cached = $prop->getValue($cmd);
        $this->assertEquals($roles1, $cached);

        // second call returns same
        $roles2 = $rm->invoke($cmd);
        $this->assertEquals($roles1, $roles2);
    }

    public function testSyncAccountDoesNothingWhenNoDesiredOrCurrentRoles()
    {
        $provider = SocialProvider::factory()->create(['code' => 'discord']);
        $user = User::factory()->create();
        $linked = LinkedAccount::factory()->create([
            'user_id' => $user->id,
            'social_provider_id' => $provider->id,
            'external_id' => 'zzz',
        ]);

        // No ticket types with discord_role_id -> shouldHave empty
        $mockApi = $this->getMockBuilder(\App\Services\DiscordApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMemberRoles', 'addRoleToMember', 'removeRoleFromMember'])
            ->getMock();

        $mockApi->expects($this->once())->method('getMemberRoles')->willReturn([
            'zzz' => (object)['id' => 'zzz', 'nickname' => 'none', 'roles' => []],
        ]);
        $mockApi->expects($this->never())->method('addRoleToMember');
        $mockApi->expects($this->never())->method('removeRoleFromMember');

        $this->app->instance(\App\Services\DiscordApi::class, $mockApi);
        $this->artisan('control:sync-discord-roles')->assertExitCode(0);
    }

    public function testHandleWithNonExistentUserArgumentDoesNotCrash()
    {
        $provider = SocialProvider::factory()->create(['code' => 'discord']);

        $mockApi = $this->getMockBuilder(\App\Services\DiscordApi::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMemberRoles'])
            ->getMock();
        $mockApi->expects($this->once())->method('getMemberRoles')->willReturn([]);
        $this->app->instance(\App\Services\DiscordApi::class, $mockApi);

        // Use a user id that does not exist
        $this->artisan('control:sync-discord-roles', ['user' => 99999])->assertExitCode(0);
    }
}
