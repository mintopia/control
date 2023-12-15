<?php

namespace Database\Seeders;

use App\Services\TicketProviders\TicketTailorProvider;
use Illuminate\Database\Seeder;

class TicketProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = [
            TicketTailorProvider::class,
        ];
        foreach ($classes as $className) {
            $provider = new $className;
            $provider->install();
        }
    }
}
