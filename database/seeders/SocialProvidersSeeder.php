<?php

namespace Database\Seeders;

use App\Services\SocialProviders\DiscordProvider;
use App\Services\SocialProviders\SteamProvider;
use App\Services\SocialProviders\TwitchProvider;
use Illuminate\Database\Seeder;

class SocialProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = [
            DiscordProvider::class,
            SteamProvider::class,
            TwitchProvider::class,
        ];
        foreach ($classes as $className) {
            $provider = new $className;
            $provider->install();
        }
    }
}
