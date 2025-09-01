<?php

namespace Database\Factories;

use App\Models\SocialProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialProviderFactory extends Factory
{
    protected $model = SocialProvider::class;

    public function definition(): array
    {
        return [
            'name' => 'Discord',
            'code' => 'discord',
            'provider_class' => 'App\\Services\\SocialProviders\\DiscordProvider',
            'supports_auth' => true,
            'enabled' => true,
            'auth_enabled' => true,
            'can_be_renamed' => false,
        ];
    }
}
