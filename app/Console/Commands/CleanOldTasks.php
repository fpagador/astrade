<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanOldTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:clean-old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete tasks and subtasks older than 3 months';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limitDate = Carbon::now()->subMonths(3)->startOfMonth();

        $this->info("Removing tasks and subtasks prior to: " . $limitDate->toDateString());

        //Backup path
        $backupPath = storage_path('app/backups');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0777, true);
        }

        $fileName = $backupPath . '/backup_tasks_' . now()->format('Y_m_d_His') . '.sql';

        //Connection data
        $dbHost = config('database.connections.mysql.host');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbName = config('database.connections.mysql.database');

        if (app()->environment('local')) {
            $this->info("Skipping backup in local environment.");
        } else {
            $dumpCmd = "mysqldump -h{$dbHost} -u{$dbUser} -p{$dbPass} {$dbName} tasks subtasks > {$fileName}";

            $this->info("Creating a backup in: {$fileName}");

            $result = null;
            system($dumpCmd, $result);

            if ($result !== 0) {
                $this->error("Error creating backup. Canceling deletion.");
                return Command::FAILURE;
            }

            $this->info("Backup completed.");
        }

        DB::beginTransaction();

        try {
            // Retrieves IDs of old tasks
            $taskIds = DB::table('tasks')
                ->where('scheduled_date', '<=', $limitDate)
                ->pluck('id');

            if ($taskIds->isEmpty()) {
                $this->info("There are no tasks to delete.");
                DB::commit();
                return Command::SUCCESS;
            }

            DB::table('subtasks')->whereIn('task_id', $taskIds)->delete();
            DB::table('tasks')->whereIn('id', $taskIds)->delete();

            DB::commit();

            $this->info("Tasks and subtasks successfully deleted.");
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
