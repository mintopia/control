<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Services\SocialProviders\DiscordProvider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class SocialProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $provider = new DiscordProvider();
        $provider->install();
    }
}
