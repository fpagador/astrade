<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Runs on the last day of the month at 23:59
        $schedule->command('logs:clear-task-completions')
            ->when(fn () => now()->isSameDay(now()->copy()->endOfMonth()))
            ->dailyAt('23:59');

        $schedule->command('logs:clear-general')
            ->when(fn () => now()->isSameDay(now()->copy()->endOfMonth()))
            ->dailyAt('23:59');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
