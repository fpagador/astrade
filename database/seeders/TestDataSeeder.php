<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Task;
use App\Models\Subtask;
use App\Models\Location;
use App\Models\LocationTask;
use App\Models\TaskCompletionLog;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        // Delete previous data
        LocationTask::truncate();
        TaskCompletionLog::truncate();
        Subtask::truncate();
        Task::truncate();
        User::truncate();

        // Create 3 fixed locations
        $locations = Location::factory()->count(3)->create();

        // Create 20 users
        $users = User::factory()->count(20)->create();

        //Change the first user to admin with a fixed ID and role 1
        $admin = $users->first();
        $admin->update([
            'dni' => '00000000A',
            'role_id' => 1,  // admin
            'password' => bcrypt('password'),
        ]);

        //Assign roles for other users randomly between manager (2) and user (3)
        foreach ($users->skip(1) as $user) {
            $user->role_id = rand(2,3);
            $user->save();
        }

        // Create tasks and assign them to users
        foreach ($users->where('role_id', 3) as $user) {
            $taskCount = rand(1, 5);
            for ($i = 0; $i < $taskCount; $i++) {
                $task = Task::factory()->create([
                    'user_id' => $user->id,
                    'assigned_by' => $users->random()->id,
                    'status' => rand(0,1) ? 'pending' : 'completed',
                ]);

                // Create between 1 and 4 subtasks per task
                $subtaskCount = rand(1,4);
                for ($j = 0; $j < $subtaskCount; $j++) {
                    $subtaskStatus = rand(0,1) ? 'pending' : 'completed';
                    $subtask = Subtask::factory()->create([
                        'task_id' => $task->id,
                        'status' => $subtaskStatus,
                    ]);

                    if ($task->status === 'completed' && $subtaskStatus === 'completed') {
                        TaskCompletionLog::create([
                            'user_id' => $user->id,
                            'task_id' => $task->id,
                            'subtask_id' => $subtask->id,
                            'completed_at' => now(),
                        ]);
                    }
                }

                //Associate task with random location
                $location = $locations->random();
                LocationTask::create([
                    'task_id' => $task->id,
                    'location_id' => $location->id,
                ]);
            }
        }
    }
}
