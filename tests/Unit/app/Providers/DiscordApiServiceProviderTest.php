<?php

namespace Tests\Unit\app\Providers;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\SocialProvider;
use App\Services\DiscordApi;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
class DiscordApiServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function testProvidesReturnsDiscordApiClass()
    {
        $provider = new \App\Providers\DiscordApiServiceProvider(app());
        $this->assertContains(DiscordApi::class, $provider->provides());
    }

    public function testRegisterBindsDiscordApiWhenProviderAndIdExist()
    {
        // Create a real SocialProvider record
        $providerInstance = SocialProvider::factory()->create();

        // Ensure Setting::fetch will return the id. Setting::fetch looks in cache first
        // so we can prime the cache by creating a Setting record without mass assignment.
        Setting::factory()->create(['code' => 'discord.server.id', 'value' => '123456', 'name' => 'Discord Server ID']);

        $app = app();
        $serviceProvider = new \App\Providers\DiscordApiServiceProvider($app);
        $serviceProvider->register();

        $resolved = $app->make(DiscordApi::class);
        $this->assertInstanceOf(DiscordApi::class, $resolved);
        // Use reflection to check private/protected properties
        $ref = new \ReflectionClass($resolved);
        $providerProp = $ref->getProperty('provider');
        $providerProp->setAccessible(true);
        $idProp = $ref->getProperty('serverId');
        $idProp->setAccessible(true);
        $this->assertEquals($providerInstance->id, $providerProp->getValue($resolved)->id);
        $this->assertEquals('123456', $idProp->getValue($resolved));
    }

    public function testRegisterBindsNullWhenNoProvider()
    {
        // Do not create a SocialProvider; create the Setting so id exists (avoid mass assignment)
        Setting::factory()->create(['code' => 'discord.server.id', 'value' => '123456', 'name' => 'Discord Server ID']);

        $app = app();
        $serviceProvider = new \App\Providers\DiscordApiServiceProvider($app);
        $serviceProvider->register();

        $resolved = $app->make(DiscordApi::class);
        $this->assertNull($resolved);
    }

    public function testRegisterBindsNullWhenNoId()
    {
        // Create provider but do not create setting
        SocialProvider::factory()->create();

        $app = app();
        $serviceProvider = new \App\Providers\DiscordApiServiceProvider($app);
        $serviceProvider->register();

        $resolved = $app->make(DiscordApi::class);
        $this->assertNull($resolved);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
