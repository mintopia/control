<?php

namespace Database\Factories;

use App\Models\TicketProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketProviderFactory extends Factory
{
    protected $model = TicketProvider::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'code' => $this->faker->unique()->word(),
            'enabled' => true,
            'provider_class' => 'App\\Services\\TicketProviders\\FakeProvider',
        ];
    }
}
