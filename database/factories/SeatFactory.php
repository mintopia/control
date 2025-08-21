<?php

namespace Database\Factories;

use App\Models\Seat;
use App\Models\SeatingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatFactory extends Factory
{
    protected $model = Seat::class;

    public function definition(): array
    {
        return [
            'seating_plan_id' => SeatingPlan::factory(),
            'x' => $this->faker->numberBetween(1, 100),
            'y' => $this->faker->numberBetween(1, 100),
            'row' => $this->faker->randomLetter(),
            'number' => $this->faker->numberBetween(1, 50),
            'label' => $this->faker->unique()->bothify('Row ? Seat ##'),
            'disabled' => false,
        ];
    }
}
