<?php

namespace App\Jobs;

use App\Models\TicketProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected TicketProvider $provider, protected string $externalId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug("{$this->provider} Synchronising ticket {$this->externalId}");
        $this->provider->syncTicket($this->externalId);
    }
}
