<?php

namespace Database\Factories;

use App\Models\EmailAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailAddressFactory extends Factory
{
    protected $model = EmailAddress::class;

    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail,
            'verification_code' => null,
            'verification_sent_at' => null,
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
