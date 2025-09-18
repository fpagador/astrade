<?php

namespace App\Console\Commands;

use App\Enums\CalendarStatus;
use Illuminate\Console\Command;
use App\Models\WorkCalendarTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignNewWorkCalendar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:assign-new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically assigns the new work schedule to all users based on continuity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting calendar assignment...');

        DB::transaction(function () {

            $lastYear = now()->subYear()->year;

            $previousCalendars = WorkCalendarTemplate::where('year', $lastYear)->get();

            foreach ($previousCalendars as $oldCalendar) {

                $this->info("Processing calendar: {$oldCalendar->name} ({$oldCalendar->year})");

                $newCalendar = $oldCalendar->continuityTemplate;

                // Deactivate previous calendar
                $oldCalendar->update(['status' => CalendarStatus::INACTIVE->value]);
                Log::info("Inactive calendar: {$oldCalendar->id} - {$oldCalendar->name}");

                if ($newCalendar) {
                    //Activate continuity calendar
                    $newCalendar->update(['status' => 'active']);
                    Log::info("Calendar activated: {$newCalendar->id} - {$newCalendar->name}");

                    //Get users assigned to the previous calendar
                    $users = User::where('work_calendar_template_id', $oldCalendar->id)->get();

                    if ($users->isNotEmpty()) {
                        foreach ($users as $user) {
                            $user->update(['work_calendar_template_id' => $newCalendar->id]);
                            Log::info("User {$user->id} ({$user->name} {$user->surname}) updated to calendar {$newCalendar->name}");
                        }
                        $this->info(count($users)." users reassigned to {$newCalendar->name}");
                    } else {
                        $this->info("There are no users assigned to the calendar {$oldCalendar->name}");
                    }

                } else {
                    $this->warn("There is no continuity calendar. Calendar users {$oldCalendar->name} are left without an assigned calendar.");
                }
            }
        });

        $this->info('Calendar assignment completed.');
    }
}
