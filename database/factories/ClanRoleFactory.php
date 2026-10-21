<?php

namespace Database\Factories;

use App\Models\ClanRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClanRoleFactory extends Factory
{
    protected $model = ClanRole::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->word(),
            'name' => $this->faker->unique()->jobTitle(),
        ];
    }
}
