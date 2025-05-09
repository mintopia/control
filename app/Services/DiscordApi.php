<?php

namespace App\Services;

use App\Models\SocialProvider;
use GuzzleHttp\Client;

class DiscordApi
{
    protected ?Client $client = null;
    protected ?array $roles = null;
    protected ?array $members = null;


    public function __construct(protected SocialProvider $provider, protected string $serverId)
    {
    }


    protected function getClient(): Client
    {
        if (!$this->client) {
            $config = [
                'base_uri' => config('services.discord.endpoint', 'https://discord.com/api/'),
                'verify' => config('services.discord.verifytls', true),
                'headers' => [
                    'Authorization' => "Bot {$this->provider->getSetting('token')}",
                ]
            ];
            $this->client = new Client($config);
        }
        return $this->client;
    }

    public function getRoles(): array
    {
        if ($this->roles !== null) {
            return $this->roles;
        }

        $response = $this->getClient()->get("guilds/{$this->serverId}/roles");
        $data = json_decode($response->getBody());

        $this->roles = [];
        foreach ($data as $role) {
            if ($role->name === '@everyone') {
                continue;
            }
            if ($role->managed) {
                continue;
            }
            $this->roles[$role->id] = $role->name;
        }
        return $this->roles;
    }

    public function getMemberRoles(): array
    {
        if ($this->members !== null) {
            return $this->members;
        }

        $this->members = [];
        $after = 0;
        do {
            $response = $this->getClient()->get("guilds/{$this->serverId}/members", [
                'query' => [
                    'limit' => 1000,
                    'after' => $after,
                ],
            ]);
            $data = json_decode($response->getBody());

            foreach ($data as $member) {
                $this->members[$member->user->id] = (object)[
                    'id' => $member->user->id,
                    'nickname' => $member->user->username,
                    'roles' => $member->roles,
                ];
                $after = $member->user->id;
            }
        } while (count($data) === 1000);

        return $this->members;
    }

    public function addRoleToMember(string $roleId, string $memberId): void
    {
        if (!$roleId || !$memberId) {
            return;
        }

        $response = $this->getClient()->put("guilds/{$this->serverId}/members/{$memberId}/roles/{$roleId}");
    }

    public function removeRoleFromMember(string $roleId, string $memberId)
    {
        if (!$roleId || !$memberId) {
            return;
        }

        $this->getClient()->delete("guilds/{$this->serverId}/members/{$memberId}/roles/{$roleId}");
    }
}
