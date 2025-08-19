<?php

namespace Database\Factories;

use App\Models\TicketType;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => $this->faker->word(),
            'discord_role_id' => $this->faker->unique()->numerify('########'),
        ];
    }
}
