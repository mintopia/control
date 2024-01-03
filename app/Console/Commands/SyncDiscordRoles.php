<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\LinkedAccount;
use App\Models\SocialProvider;
use App\Models\TicketType;
use App\Models\User;
use App\Services\DiscordApi;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDiscordRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'control:sync-discord-roles {user?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise discord roles';

    protected ?array $managedRoles = null;
    protected ?DiscordApi $discord;

    /**
     * Execute the console command.
     */
    public function handle(?DiscordApi $discord)
    {
        $this->discord = $discord;

        if ($this->discord === null) {
            Log::debug("Unable to sync Discord roles, no API access");
            return;
        }
        $discordProvider = SocialProvider::whereCode('discord')->first();
        if (!$discordProvider) {
            Log::debug("Unable to sync Discord roles, Social Provider was not found");
            return;
        }

        $userId = $this->argument('user');
        $user = null;
        if ($userId) {
            $user = User::whereId($userId)->first();
        }

        $members = $this->getDiscordMembers();
        $ids = array_keys($members);
        $query = LinkedAccount::query();
        if ($user) {
            $this->output->writeln("{$user}: Explicitly synchronising Discord groups");
            $query = $query->where('user_id', $user->id);
        }
        $query->whereSocialProviderId($discordProvider->id)
            ->whereIn('external_id', $ids)
            ->with(['user', 'user.tickets', 'user.tickets.type'])
            ->chunk(100, function($accounts) use ($members) {
                foreach ($accounts as $linkedAccount) {
                    $this->syncAccount($linkedAccount, $members[$linkedAccount->external_id]);
                }
            });
    }

    protected function getDiscordMembers(): array
    {
        if (!$this->discord) {
            return [];
        }
        return $this->discord->getMemberRoles();
    }

    protected function syncAccount(LinkedAccount $account, object $discordMember): void
    {
        $this->output->writeln("{$account->user}: Syncing discord groups");
        $managedRoles = $this->getManagedRoles();
        $shouldHave = [];
        foreach ($account->user->tickets as $ticket) {
            if ($ticket->type->discord_role_id && !in_array($ticket->type->discord_role_id, $shouldHave)) {
                $shouldHave[] = $ticket->type->discord_role_id;
            }
        }
        $toAdd = $shouldHave;
        $toRemove = [];
        foreach ($discordMember->roles as $role) {
            if (in_array($role, $toAdd)) {
                unset($toAdd[array_search($role, $toAdd)]);
            } elseif (in_array($role, $managedRoles)) {
                $toRemove[] = $role;
            }
        }

        foreach ($toAdd as $role) {
            $this->output->writeln("    Adding Discord role {$role}");
            Log::info("{$account->user} adding Discord role {$role}");
            $this->discord->addRoleToMember($role, $account->external_id);
        }

        foreach ($toRemove as $role) {
            $this->output->writeln("    Removing Discord role {$role}");
            Log::info("{$account->user} removing Discord role {$role}");
            $this->discord->removeRoleFromMember($role, $account->external_id);
        }
    }

    protected function getManagedRoles(): array
    {
        if ($this->managedRoles !== null) {
            return $this->managedRoles;
        }
        $this->managedRoles = array_unique(TicketType::query()
            ->whereNotNull('discord_role_id')
            ->pluck('discord_role_id')
            ->toArray());
        return $this->managedRoles;
    }
}
