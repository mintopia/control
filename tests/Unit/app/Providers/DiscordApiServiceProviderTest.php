<?php

namespace Tests\Unit\app\Providers;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\SocialProvider;
use App\Services\DiscordApi;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Illuminate\Support\Facades\App;

/**
 * @runTestsInSeparateProcesses
 */
class DiscordApiServiceProviderTest extends TestCase
{
    public function testProvidesReturnsDiscordApiClass()
    {
        $provider = new \App\Providers\DiscordApiServiceProvider(app());
        $this->assertContains(DiscordApi::class, $provider->provides());
    }

    // FIXME Mockery\Exception\RuntimeException: Could not load mock App\Models\Setting, class already exists
    // public function testRegisterBindsDiscordApiWhenProviderAndIdExist()
    // {
    //     // Mock SocialProvider::whereCode('discord')->first() to return a provider
    //     $providerMock = \Mockery::mock('alias:App\\Models\\SocialProvider');
    //     $providerInstance = new \stdClass();
    //     $providerMock->shouldReceive('whereCode')->with('discord')->andReturnSelf();
    //     $providerMock->shouldReceive('first')->andReturn($providerInstance);

    //     // Mock Setting::fetch using Mockery alias
    //     $settingMock = \Mockery::mock('alias:App\\Models\\Setting');
    //     $settingMock->shouldReceive('fetch')->with('discord.server.id')->andReturn('123456');

    //     $app = app();
    //     $serviceProvider = new \App\Providers\DiscordApiServiceProvider($app);
    //     $serviceProvider->register();

    //     $resolved = $app->make(DiscordApi::class);
    //     $this->assertInstanceOf(DiscordApi::class, $resolved);
    //     // Use reflection to check private/protected properties
    //     $ref = new \ReflectionClass($resolved);
    //     $providerProp = $ref->getProperty('provider');
    //     $providerProp->setAccessible(true);
    //     $idProp = $ref->getProperty('id');
    //     $idProp->setAccessible(true);
    //     $this->assertEquals($providerInstance, $providerProp->getValue($resolved));
    //     $this->assertEquals('123456', $idProp->getValue($resolved));
    // }

    // public function testRegisterBindsNullWhenNoProvider()
    // {
    //     $providerMock = \Mockery::mock('alias:App\\Models\\SocialProvider');
    //     $providerMock->shouldReceive('whereCode')->with('discord')->andReturnSelf();
    //     $providerMock->shouldReceive('first')->andReturn(null);

    //     $settingMock = \Mockery::mock('alias:App\\Models\\Setting');
    //     $settingMock->shouldReceive('fetch')->with('discord.server.id')->andReturn('123456');

    //     $app = app();
    //     $serviceProvider = new \App\Providers\DiscordApiServiceProvider($app);
    //     $serviceProvider->register();

    //     $resolved = $app->make(DiscordApi::class);
    //     $this->assertNull($resolved);
    // }

    // public function testRegisterBindsNullWhenNoId()
    // {
    //     $providerMock = \Mockery::mock('alias:App\\Models\\SocialProvider');
    //     $providerInstance = new \stdClass();
    //     $providerMock->shouldReceive('whereCode')->with('discord')->andReturnSelf();
    //     $providerMock->shouldReceive('first')->andReturn($providerInstance);

    //     $settingMock = \Mockery::mock('alias:App\\Models\\Setting');
    //     $settingMock->shouldReceive('fetch')->with('discord.server.id')->andReturn(null);

    //     $app = app();
    //     $serviceProvider = new \App\Providers\DiscordApiServiceProvider($app);
    //     $serviceProvider->register();

    //     $resolved = $app->make(DiscordApi::class);
    //     $this->assertNull($resolved);
    // }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
