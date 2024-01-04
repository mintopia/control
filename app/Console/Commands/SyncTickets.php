<?php

namespace App\Console\Commands;

use App\Models\LinkedAccount;
use App\Models\TicketProvider;
use App\Models\TicketType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'control:sync-tickets {provider?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise all tickets for providers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $providerCode = $this->argument('provider');
        $providers = [];
        if ($providerCode) {
            $provider = TicketProvider::whereCode($providerCode)->first();
            if ($provider) {
                $providers[] = $provider;
            }
        } else {
            $providers = TicketProvider::whereEnabled(true)->get();
        }

        foreach ($providers as $provider) {
            $prov = $provider->getProvider();
            Log::info("{$provider}: Synchronising tickets");
            $this->output->writeln("Synchronising all tickets for {$provider}");
            $prov->syncAllTickets($this->output);
            Log::info("{$provider}: Finished synchronising");
            $this->output->writeln("Finished synchronising all tickets for {$provider}");
        }
    }
}
