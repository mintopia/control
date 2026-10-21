<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // UPDATED 10-AUG 2025 changed to below for connection to email address / was:
            // 'name' => fake()->name(),
            // 'email' => fake()->unique()->safeEmail(),
            // 'email_verified_at' => now(),
            // 'password' => static::$password ??= Hash::make('password'),
            // 'remember_token' => Str::random(10),

            'nickname' => fake()->userName(),
            'name' => fake()->name(),
            'avatar' => fake()->imageUrl(200, 200, 'people'),
            'terms_agreed_at' => now(),
            'first_login' => now(),
            'last_login' => now(),
            'suspended' => false,
            'primary_email_id' => null,
            'tickets_synced_at' => null,
        ];
    }

    //CHECK that the $table->foreignId('user_id')->constrained()->cascadeOnDelete(); on table email_addresses is always unique!
    /**
     * Create a related email address for the user and links it to the user account.

     */
    public function withEmailAddresses($count = 1)
    {
        return $this->has(\App\Models\EmailAddress::factory()->count($count), 'emails');
    }

    public function withEmailAddress()
    {
        return $this->has(\App\Models\EmailAddress::factory(), 'emails')
            ->afterCreating(function (User $user) {
                $user->primary_email_id = $user->emails()->first()->id;
                $user->save();
            });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
