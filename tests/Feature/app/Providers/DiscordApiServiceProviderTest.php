<?php

namespace Tests\Feature\app\Providers;

use Tests\TestCase;
use App\Models\Setting;
use App\Models\SocialProvider;
use App\Services\DiscordApi;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mockery;
use Illuminate\Support\Facades\App;

class DiscordApiServiceProviderTest extends TestCase
{
    public function testProvidesReturnsDiscordApiClass()
    {
        $provider = new \app\Providers\DiscordApiServiceProvider(app());
        $this->assertContains(DiscordApi::class, $provider->provides());
    }
    
    // CHECK Class already exists with Mocking
    // public function testRegistersDiscordApiSingleton()
    // {
    //     // Create partial mocks for SocialProvider and Setting
    //     $socialProviderMock = \Mockery::mock('overload:App\Models\SocialProvider');
    //     $socialProviderMock->shouldReceive('whereCode')->with('discord')->andReturnSelf();
    //     $socialProviderMock->shouldReceive('first')->andReturn((object)['id' => 1]);

    //     $settingMock = \Mockery::mock('overload:App\Models\Setting');
    //     $settingMock->shouldReceive('fetch')->with('discord.server.id')->andReturn(123);

    //     $provider = new \app\Providers\DiscordApiServiceProvider(app());
    //     $provider->register();
    //     $instance = app(DiscordApi::class);
    //     $this->assertInstanceOf(DiscordApi::class, $instance);
    // }

}
