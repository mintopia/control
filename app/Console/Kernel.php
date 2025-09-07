<?php

namespace App\Console;

use App\Console\Commands\UpdateEventSeatingLock;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's commands.
     */
    protected $commands = [
        UpdateEventSeatingLock::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('sanctum:prune-expired --hours=24')->daily();
        $schedule->command('telescope:prune')->daily();
        $schedule->command('control:prune-clans')->daily();
        $schedule->command('control:update-event-seating-locks')->daily();
        $schedule->command('control:sync-discord-roles')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
