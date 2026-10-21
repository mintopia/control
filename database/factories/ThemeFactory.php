<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Theme;

class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    public function definition()
    {
        return [
            'name' => 'Default',
            'code' => 'default',
            'readonly' => 0,
            'active' => 1,
            'dark_mode' => 1,
            'primary' => '#000000',
            'nav_background' => '#ffffff',
            'seat_available' => '#00ff00',
            'seat_disabled' => '#cccccc',
            'seat_taken' => '#ff0000',
            'seat_clan' => '#0000ff',
            'seat_selected' => '#ffff00',
            'css' => null,
        ];
    }
}
