<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            // Use unique name and explicit unique code so concurrent test factories
            // don't produce duplicate event codes (avoids UNIQUE constraint errors
            // when observers generate codes from names).
            'name' => $this->faker->unique()->word(),
            'code' => $this->faker->unique()->bothify('EVT-####'),
            'seating_opens_at' => null,
            'seating_closes_at' => null,
            'seating_locked' => true,
        ];
    }

    /**
     * State: event where seating opened in the past (should be unlocked).
     */
    public function opened(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'seating_opens_at' => now()->subMinute(),
                'seating_locked' => false,
            ];
        });
    }

    /**
     * State: event where seating closed in the past (should be locked).
     */
    public function closed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'seating_closes_at' => now()->subMinute(),
                'seating_locked' => true,
            ];
        });
    }
}
