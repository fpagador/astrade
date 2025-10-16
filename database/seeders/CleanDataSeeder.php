<?php

namespace Database\Seeders;

use App\Models\Log;
use App\Models\RecurrentTask;
use App\Models\UserAbsence;
use App\Models\WorkCalendarDay;
use App\Models\WorkCalendarTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Task;
use App\Models\Subtask;
use App\Models\Company;
use App\Models\CompanyPhone;
use App\Models\TaskCompletionLog;
use Illuminate\Support\Facades\File;

class CleanDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checking
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clean tables
        Company::truncate();
        CompanyPhone::truncate();
        Log::truncate();
        RecurrentTask::truncate();
        TaskCompletionLog::truncate();
        Subtask::truncate();
        Task::truncate();
        TaskCompletionLog::truncate();
        User::truncate();
        UserAbsence::truncate();
        WorkCalendarDay::truncate();
        WorkCalendarTemplate::truncate();

        // Enable foreign key checking
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Clean files
        $this->cleanDirectory(public_path('storage/photos'));
        $this->cleanDirectory(public_path('storage/pictograms'));
    }

    /**
     * Deletes all files in a directory but keeps the directory.
     */
    private function cleanDirectory(string $path): void
    {
        if (File::exists($path)) {
            File::files($path) && File::delete(File::files($path));

            foreach (File::directories($path) as $dir) {
                File::deleteDirectory($dir);
            }
        }
    }
}
