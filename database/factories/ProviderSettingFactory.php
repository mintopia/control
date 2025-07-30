<?php

namespace Database\Factories;

use App\Models\ProviderSetting;
use App\Models\SocialProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderSettingFactory extends Factory
{
    protected $model = ProviderSetting::class;

    public function definition(): array
    {
        return [
            'provider_type' => SocialProvider::class,
            'provider_id' => SocialProvider::factory(),
            'name' => $this->faker->word(),
            'code' => $this->faker->unique()->word(),
            'type' => 'string',
            'encrypted' => false,
            'validation' => null,
            'value' => $this->faker->word(),
            'order' => 1,
        ];
    }
}
