<?php

namespace App\Jobs;

use App\Models\EmailAddress;
use App\Models\TicketProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTicketsForEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected EmailAddress $email)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->email->verified_at === null) {
            Log::debug("{$this->email->user} {$this->email} Failed synchronising tickets, email is not confirmed");
            return;
        }
        $providers = TicketProvider::whereEnabled(true)->get();
        foreach ($providers as $provider) {
            Log::debug("{$this->email->user} {$this->email} Synchronising tickets for {$provider}");
            $provider->syncTickets($this->email);
        }
    }
}
