<?php

namespace Database\Factories;

use App\Models\EmailAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = EmailAddress::class;

    public function definition()
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'verification_code' => null,
            'verification_sent_at' => null,
            'user_id' => User::factory(),
        ];
    }
}
