<?php

namespace App\Console\Commands;

use App\Console\AdminNotifier;
use App\Notifications\LogsClearedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClearLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:clear-general';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete general logs older than one month';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoff = Carbon::now()->subMonth();

        $deleted = DB::table('logs')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} general logs older than one month.");
    }
}
