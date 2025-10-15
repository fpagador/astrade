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

class CleanDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
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



        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
