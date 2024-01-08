<?php

namespace Database\Seeders;

use App\Services\TicketProviders\GenericTicketProvider;
use App\Services\TicketProviders\InternalTicketProvider;
use App\Services\TicketProviders\TicketTailorProvider;
use App\Services\TicketProviders\WooCommerceProvider;
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
            InternalTicketProvider::class,
            WooCommerceProvider::class,
            GenericTicketProvider::class,
        ];
        foreach ($classes as $className) {
            $provider = new $className;
            $provider->install();
        }
    }
}
