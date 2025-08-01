<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Console\AdminNotifier;
use App\Notifications\LogsClearedNotification;

class ClearTaskCompletionLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clear-task-completions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete task completion logs older than 1 month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoff = Carbon::now()->subMonth();

        $deleted = DB::table('task_completion_logs')
            ->where('completed_at', '<', $cutoff)
            ->delete();

        // Notify the administrator by email
        (new AdminNotifier)->notify(
            new LogsClearedNotification('Task Completion', $deleted)
        );

        $this->info("Deleted {$deleted} task completion log(s) older than one month.");
    }
}
