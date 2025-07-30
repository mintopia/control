<?php

namespace Database\Factories;

use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'discord_role_id' => $this->faker->unique()->numerify('########'),
        ];
    }
}
