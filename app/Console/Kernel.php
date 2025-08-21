<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's commands.
     */
    protected $commands = [
        \App\Console\Commands\UpdateEventSeatingLock::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('sanctum:prune-expired --hours=24')->daily()->description('sanctum:prune-expired --hours=24');
        $schedule->command('telescope:prune')->daily()->description('telescope:prune');
        $schedule->command('control:prune-clans')->daily()->description('control:prune-clans');
        $schedule->command('control:update-event-seating-locks')->daily()->description('control:update-event-seating-locks');
        $schedule->command('control:sync-discord-roles')->daily()->description('control:sync-discord-roles');
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
