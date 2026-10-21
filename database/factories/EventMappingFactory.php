<?php

namespace Database\Factories;

use App\Models\EventMapping;
use App\Models\Event;
use App\Models\TicketProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventMappingFactory extends Factory
{
    protected $model = EventMapping::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_provider_id' => TicketProvider::factory(),
            'external_id' => $this->faker->unique()->numerify('#####'),
            'name' => null,
        ];
    }
}
