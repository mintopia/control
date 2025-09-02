<?php

namespace Tests\Unit\app\Services;

use Tests\TestCase;
use App\Services\DiscordApi;
use App\Models\SocialProvider;
use App\Models\ProviderSetting;
use GuzzleHttp\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DiscordApiTest extends TestCase
{
    use RefreshDatabase;

    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    public function testGetClientReturnsClientInstance()
    {
        $provider = SocialProvider::factory()->create();
        ProviderSetting::factory()->create([
            'provider_type' => SocialProvider::class,
            'provider_id' => $provider->id,
            'code' => 'token',
            'value' => 'fake-token',
        ]);

        $discordApi = new DiscordApi($provider, 'server-id');
        $client = $this->invokeMethod($discordApi, 'getClient');
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testConstructorSetsProperties()
    {
        $provider = SocialProvider::factory()->create();
        ProviderSetting::factory()->create([
            'provider_type' => SocialProvider::class,
            'provider_id' => $provider->id,
            'code' => 'token',
            'value' => 'fake-token',
        ]);

        $discordApi = new DiscordApi($provider, 'guild123');
        $reflection = new \ReflectionClass($discordApi);
        $providerProp = $reflection->getProperty('provider');
        $providerProp->setAccessible(true);
        $serverIdProp = $reflection->getProperty('serverId');
        $serverIdProp->setAccessible(true);

        $this->assertSame($provider, $providerProp->getValue($discordApi));
        $this->assertEquals('guild123', $serverIdProp->getValue($discordApi));
    }

    public function testGetMemberRolesCallsClientWithCorrectEndpoint()
    {
        $provider = SocialProvider::factory()->create();
        ProviderSetting::factory()->create([
            'provider_type' => SocialProvider::class,
            'provider_id' => $provider->id,
            'code' => 'token',
            'value' => 'fake-token',
        ]);

        $discordApi = new DiscordApi($provider, 'guild123');

        // Fake client captures calls and returns a single member for the members GET
        $fake = new class extends Client {
            public $calls = [];
            public function request(string $method, $uri = '', array $options = []): \Psr\Http\Message\ResponseInterface
            {
                $this->calls[] = ['method' => strtoupper($method), 'uri' => $uri, 'options' => $options];
                if (strtoupper($method) === 'GET' && str_contains($uri, '/members')) {
                    $data = [
                        (object)[
                            'user' => (object)['id' => '100', 'username' => 'bob'],
                            'roles' => ['r1', 'r2']
                        ]
                    ];
                    return new \GuzzleHttp\Psr7\Response(200, [], json_encode($data));
                }
                return new \GuzzleHttp\Psr7\Response(204);
            }
        };

        $ref = new \ReflectionClass($discordApi);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($discordApi, $fake);

        // Call the real method and assert it triggered a members GET and returned the member
        $members = $discordApi->getMemberRoles();
        $this->assertCount(1, $fake->calls);
        $this->assertStringContainsString('guilds/guild123/members', $fake->calls[0]['uri']);
        $this->assertArrayHasKey('100', $members);
    }

    public function testAddRoleToMemberCallsClientWithCorrectEndpoint()
    {
        $provider = SocialProvider::factory()->create();
        ProviderSetting::factory()->create([
            'provider_type' => SocialProvider::class,
            'provider_id' => $provider->id,
            'code' => 'token',
            'value' => 'fake-token',
        ]);

        $discordApi = new DiscordApi($provider, 'guild123');

        $fake = new class extends Client {
            public $calls = [];
            public function request(string $method, $uri = '', array $options = []): \Psr\Http\Message\ResponseInterface
            {
                $this->calls[] = ['method' => strtoupper($method), 'uri' => $uri, 'options' => $options];
                return new \GuzzleHttp\Psr7\Response(204);
            }
        };

        $ref = new \ReflectionClass($discordApi);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($discordApi, $fake);

        // Call the real method addRoleToMember(roleId, memberId)
        $discordApi->addRoleToMember('role123', 'user123');
        $this->assertCount(1, $fake->calls);
        $this->assertStringContainsString('guilds/guild123/members/user123/roles/role123', $fake->calls[0]['uri']);
    }

    public function testRemoveRoleFromMemberCallsClientWithCorrectEndpoint()
    {
        $provider = SocialProvider::factory()->create();
        ProviderSetting::factory()->create([
            'provider_type' => SocialProvider::class,
            'provider_id' => $provider->id,
            'code' => 'token',
            'value' => 'fake-token',
        ]);

        $discordApi = new DiscordApi($provider, 'guild123');

        $fake = new class extends Client {
            public $calls = [];
            public function request(string $method, $uri = '', array $options = []): \Psr\Http\Message\ResponseInterface
            {
                $this->calls[] = ['method' => strtoupper($method), 'uri' => $uri, 'options' => $options];
                return new \GuzzleHttp\Psr7\Response(204);
            }
        };

        $ref = new \ReflectionClass($discordApi);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($discordApi, $fake);

        // Call the real method removeRoleFromMember(roleId, memberId)
        $discordApi->removeRoleFromMember('role123', 'user123');
        $this->assertCount(1, $fake->calls);
        $this->assertStringContainsString('guilds/guild123/members/user123/roles/role123', $fake->calls[0]['uri']);
    }

    public function testGetRolesCallsClientWithCorrectEndpoint()
    {
        $provider = SocialProvider::factory()->create();
        ProviderSetting::factory()->create([
            'provider_type' => SocialProvider::class,
            'provider_id' => $provider->id,
            'code' => 'token',
            'value' => 'fake-token',
        ]);

        $discordApi = new DiscordApi($provider, 'guild123');

        $fake = new class extends Client {
            public $calls = [];
            public function request(string $method, $uri = '', array $options = []): \Psr\Http\Message\ResponseInterface
            {
                $this->calls[] = ['method' => strtoupper($method), 'uri' => $uri, 'options' => $options];

                // Return an array of role objects as the real API does.
                $data = [
                    (object)['id' => 'r1', 'name' => 'role1', 'managed' => false],
                    (object)['id' => 'r2', 'name' => 'role2', 'managed' => false],
                    // include some roles that should be filtered out by getRoles()
                    (object)['id' => 'r3', 'name' => '@everyone', 'managed' => false],
                    (object)['id' => 'r4', 'name' => 'bot-role', 'managed' => true],
                ];

                return new \GuzzleHttp\Psr7\Response(200, [], json_encode($data));
            }
        };

        $ref = new \ReflectionClass($discordApi);
        $prop = $ref->getProperty('client');
        $prop->setAccessible(true);
        $prop->setValue($discordApi, $fake);

        // Call the real method getRoles()
        $roles = $discordApi->getRoles();
        $this->assertCount(1, $fake->calls);
        $this->assertStringContainsString('guilds/guild123/roles', $fake->calls[0]['uri']);
        $this->assertEquals(['r1' => 'role1', 'r2' => 'role2'], $roles);
    }
}
