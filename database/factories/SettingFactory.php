<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    protected $model = Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->word(),
            'name' => fake()->words(3, true),
            'description' => null,
            'encrypted' => 0,
            'value' => fake()->word(),
            'validation' => null,
            'type' => 'stString',
            'order' => 1,
        ];
    }
}
