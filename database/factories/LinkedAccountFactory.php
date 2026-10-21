<?php

namespace Database\Factories;

use App\Models\LinkedAccount;
use App\Models\User;
use App\Models\SocialProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class LinkedAccountFactory extends Factory
{
    protected $model = LinkedAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'social_provider_id' => SocialProvider::factory(),
            'external_id' => $this->faker->unique()->numerify('########'),
        ];
    }
}
