<?php

namespace Database\Seeders;

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
        TaskCompletionLog::truncate();
        Subtask::truncate();
        Task::truncate();
        CompanyPhone::truncate();
        User::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
