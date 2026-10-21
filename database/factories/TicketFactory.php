<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\TicketProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'ticket_provider_id' => TicketProvider::factory(),
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'ticket_type_id' => TicketType::factory(),
            'external_id' => $this->faker->unique()->uuid(),
            'name' => $this->faker->name(),
            'reference' => $this->faker->unique()->uuid(),
        ];
    }
}
