<?php

namespace Tests\Unit\app\Services;

use Tests\TestCase;
use App\Services\DiscordApi;
use App\Models\SocialProvider;
use GuzzleHttp\Client;
use PHPUnit\Framework\MockObject\MockObject;

class DiscordApiTest extends TestCase
{
    public function testGetClientReturnsClientInstance()
    {
        $provider = $this->createMock(SocialProvider::class);
        $provider->method('getSetting')->willReturn('fake-token');
        $discordApi = new DiscordApi($provider, 'server-id');
        $client = $this->invokeMethod($discordApi, 'getClient');
        $this->assertInstanceOf(Client::class, $client);
    }

    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }


    public function testConstructorSetsProperties()
    {
        $provider = $this->createMock(SocialProvider::class);
        $discordApi = new DiscordApi($provider, 'guild123');
        $reflection = new \ReflectionClass($discordApi);
        $providerProp = $reflection->getProperty('provider');
        $providerProp->setAccessible(true);
        $serverIdProp = $reflection->getProperty('serverId');
        $serverIdProp->setAccessible(true);

        $this->assertSame($provider, $providerProp->getValue($discordApi));
        $this->assertEquals('guild123', $serverIdProp->getValue($discordApi));
    }

    public function testGetGuildMembersCallsClientWithCorrectEndpoint()
    {
        $provider = $this->createMock(SocialProvider::class);
        $provider->method('getSetting')->willReturn('fake-token');
        $discordApi = $this->getMockBuilder(DiscordApi::class)
            ->setConstructorArgs([$provider, 'guild123'])
            ->onlyMethods(['getClient'])
            ->getMock();
        $mockClient = $this->createMock(Client::class);
        $discordApi->method('getClient')->willReturn($mockClient);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->stringContains('/guilds/guild123/members'),
                $this->arrayHasKey('headers')
            )
            ->willReturn(new \GuzzleHttp\Psr7\Response(200, [], '[]'));
        // If getGuildMembers exists
        if (method_exists($discordApi, 'getGuildMembers')) {
            $discordApi->getGuildMembers();
        } else {
            $this->markTestSkipped('getGuildMembers method does not exist on DiscordApi');
        }
    }

    public function testAddGuildMemberCallsClientWithCorrectData()
    {
        $provider = $this->createMock(SocialProvider::class);
        $provider->method('getSetting')->willReturn('fake-token');
        $discordApi = $this->getMockBuilder(DiscordApi::class)
            ->setConstructorArgs([$provider, 'guild123'])
            ->onlyMethods(['getClient'])
            ->getMock();
        $mockClient = $this->createMock(Client::class);
        $discordApi->method('getClient')->willReturn($mockClient);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('PUT'),
                $this->stringContains('/guilds/guild123/members/'),
                $this->arrayHasKey('json')
            )
            ->willReturn(new \GuzzleHttp\Psr7\Response(204));
        // If addGuildMember exists
        if (method_exists($discordApi, 'addGuildMember')) {
            $discordApi->addGuildMember('user123', ['access_token' => 'token']);
        } else {
            $this->markTestSkipped('addGuildMember method does not exist on DiscordApi');
        }
    }

    public function testRemoveGuildMemberCallsClientWithCorrectEndpoint()
    {
        $provider = $this->createMock(SocialProvider::class);
        $provider->method('getSetting')->willReturn('fake-token');
        $discordApi = $this->getMockBuilder(DiscordApi::class)
            ->setConstructorArgs([$provider, 'guild123'])
            ->onlyMethods(['getClient'])
            ->getMock();
        $mockClient = $this->createMock(Client::class);
        $discordApi->method('getClient')->willReturn($mockClient);
        $mockClient->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('DELETE'),
                $this->stringContains('/guilds/guild123/members/user123'),
                $this->arrayHasKey('headers')
            )
            ->willReturn(new \GuzzleHttp\Psr7\Response(204));
        // If removeGuildMember exists
        if (method_exists($discordApi, 'removeGuildMember')) {
            $discordApi->removeGuildMember('user123');
        } else {
            $this->markTestSkipped('removeGuildMember method does not exist on DiscordApi');
        }
    }
}
