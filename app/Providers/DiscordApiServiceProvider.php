<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\SocialProvider;
use App\Services\DiscordApi;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class DiscordApiServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(DiscordApi::class, function($app) {
            $provider = SocialProvider::whereCode('discord')->first();
            $id = Setting::fetch('discord.server.id');
            if (!$provider || !$id) {
                return null;
            }
            return new DiscordApi($provider, $id);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    public function provides()
    {
        return [
            DiscordApi::class,
        ];
    }

    public function defer()
    {
        return [
            DiscordApi::class,
        ];
    }
}
