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
}
