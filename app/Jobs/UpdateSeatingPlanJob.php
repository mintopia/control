<?php

namespace App\Jobs;

use App\Models\SeatingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateSeatingPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public SeatingPlan $plan, public int $revision)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->plan->revision !== $this->revision) {
            Log::debug("{$this->plan} not updating as {$this->plan->revision} <> {$this->revision}");
            return;
        }

        Log::debug("{$this->plan} triggering update from job");
        $this->plan->getData();
    }
}
