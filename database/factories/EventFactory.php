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
            'name' => $this->faker->word(),
            'seating_opens_at' => null,
            'seating_closes_at' => null,
            'seating_locked' => true,
        ];
    }
}
