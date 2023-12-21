<?php

namespace App\Console\Commands;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateEventSeatingLock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'control:update-event-seating-locks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Event::where('seating_opens_at', '<', Carbon::now())->chunk(100, function($chunk) {
            foreach ($chunk as $event) {
                $this->output->writeln("{$event} Unlocking seating");
                Log::info("{$event}: Unlocking seating");
                $event->seating_locked = false;
                $event->seating_opens_at = null;
                $event->save();
            }
        });
        Event::where('seating_closes_at', '<', Carbon::now())->chunk(100, function($chunk) {
            foreach ($chunk as $event) {
                $this->output->writeln("{$event} Locking seating");
                Log::info("{$event}: Locking seating");
                $event->seating_locked = false;
                $event->seating_closes_at = null;
                $event->save();
            }
        });
    }
}
