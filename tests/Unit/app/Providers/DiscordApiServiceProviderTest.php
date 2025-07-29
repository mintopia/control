<?php

namespace Tests\Unit\App\Providers;

use Tests\TestCase;

use App\Models\Setting;
use App\Models\SocialProvider;
use App\Services\DiscordApi;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class DiscordApiServiceProviderTest extends TestCase
{
    public function testRegistersDiscordApiSingleton()
    {
        $provider = new \App\Providers\DiscordApiServiceProvider(app());
        // Mock SocialProvider and Setting
        SocialProvider::shouldReceive('whereCode')->with('discord')->andReturnSelf();
        SocialProvider::shouldReceive('first')->andReturn((object)['id' => 1]);
        Setting::shouldReceive('fetch')->with('discord.server.id')->andReturn(123);
        $provider->register();
        $instance = app(DiscordApi::class);
        $this->assertInstanceOf(DiscordApi::class, $instance);
    }

    public function testProvidesReturnsDiscordApiClass()
    {
        $provider = new \App\Providers\DiscordApiServiceProvider(app());
        $this->assertContains(DiscordApi::class, $provider->provides());
    }
}
