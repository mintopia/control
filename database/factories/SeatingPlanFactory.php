<?php

namespace Database\Factories;

use App\Models\SeatingPlan;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatingPlanFactory extends Factory
{
    protected $model = SeatingPlan::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => $this->faker->word(),
            'code' => $this->faker->unique()->bothify('PLAN-####'),
            'order' => $this->faker->numberBetween(1, 10),
            'scale' => 1,
            'revision' => 1,
        ];
    }
}
