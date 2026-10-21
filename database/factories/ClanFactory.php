<?php

namespace Database\Factories;

use App\Models\Clan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClanFactory extends Factory
{
    protected $model = Clan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'invite_code' => strtoupper(Str::random(8)),
        ];
    }
}
