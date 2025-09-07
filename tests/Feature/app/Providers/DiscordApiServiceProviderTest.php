<?php

namespace Tests\Feature\app\Providers;

use App\Models\Setting;
use App\Models\SocialProvider;
use App\Providers\DiscordApiServiceProvider;
use App\Services\DiscordApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscordApiServiceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function testProvidesReturnsDiscordApiClass()
    {
        $provider = new DiscordApiServiceProvider(app());
        $this->assertContains(DiscordApi::class, $provider->provides());
    }

    // Use real Eloquent models via factories to avoid Mockery alias collisions
    public function testRegistersDiscordApiSingleton()
    {
        $providerInstance = SocialProvider::factory()->create();
        Setting::factory()->create(['code' => 'discord.server.id', 'value' => '123']);

        $provider = new DiscordApiServiceProvider(app());
        $provider->register();
        $instance = app(DiscordApi::class);
        $this->assertInstanceOf(DiscordApi::class, $instance);
    }
}
