<?php

namespace Tests\Feature\app\Providers;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\SocialProvider;
use App\Services\DiscordApi;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;

class DiscordApiServiceProviderTest extends TestCase
{
    use RefreshDatabase;
    public function testProvidesReturnsDiscordApiClass()
    {
        $provider = new \app\Providers\DiscordApiServiceProvider(app());
        $this->assertContains(DiscordApi::class, $provider->provides());
    }

    // Use real Eloquent models via factories to avoid Mockery alias collisions
    public function testRegistersDiscordApiSingleton()
    {
        $providerInstance = SocialProvider::factory()->create();
        Setting::factory()->create(['code' => 'discord.server.id', 'value' => '123']);

        $provider = new \App\Providers\DiscordApiServiceProvider(app());
        $provider->register();
        $instance = app(DiscordApi::class);
        $this->assertInstanceOf(DiscordApi::class, $instance);
    }
}
