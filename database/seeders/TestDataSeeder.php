<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Task;
use App\Models\Subtask;
use App\Models\Company;
use App\Models\CompanyPhone;
use App\Models\TaskCompletionLog;
use App\Models\WorkCalendarTemplate;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        $password = 'TestPass2025!';

        // Crear empresas
        $companies = Company::factory()->count(3)->create();

        // Teléfonos de empresas
        foreach ($companies as $company) {
            for ($i = 0; $i < rand(1, 2); $i++) {
                CompanyPhone::create([
                    'company_id' => $company->id,
                    'phone_number' => fake()->randomElement(['6','7']) . fake()->numerify('########') ,
                    'name' => fake()->optional()->words(2, true),
                ]);
            }
        }

        // Plantillas laborales
        $templates = WorkCalendarTemplate::factory()
            ->count(3)
            ->withDays()
            ->create();

        // Crear 20 usuarios con contraseña y empresa asignada
        $users = collect();
        for ($i = 0; $i < 20; $i++) {
            $users->push(User::factory()->create([
                'company_id' => null,
                'work_calendar_template_id' => null,
                'password' => Hash::make($password),
                'role_id' => 3,
            ]));
        }

        // Usuario admin fijo
        $admin = $users->first();
        $admin->update([
            'dni' => '01035080L',
            'role_id' => 1,
            'company_id' => null, // Admin sin empresa
            'work_calendar_template_id' => null, // Admin sin calendar
        ]);

        // Resto de usuarios: manager o user
        foreach ($users->skip(1) as $user) {
            $newRole = rand(2, 3); // 2 = manager, 3 = user
            $user->update([
                'role_id' => $newRole,
            ]);

            if ($newRole === 3) {
                // Solo usuarios tipo user tienen empresa y plantilla laboral
                $user->update([
                    'company_id' => $companies->random()->id,
                    'work_calendar_template_id' => $templates->random()->id,
                ]);
            }
        }

        // Usuarios tipo "user"
        $userUsers = $users->where('role_id', 3)->values();

        // Tareas para usuarios tipo "user"
        foreach ($userUsers as $user) {
            $taskCount = rand(1, 5);
            for ($i = 0; $i < $taskCount; $i++) {
                $task = Task::factory()->create([
                    'user_id' => $user->id,
                    'assigned_by' => $users->random()->id,
                    'status' => rand(0, 1) ? 'pending' : 'completed',
                ]);
                $subtaskCount = rand(1, 4);
                for ($j = 0; $j < $subtaskCount; $j++) {
                    $subtaskStatus = rand(0, 1) ? 'pending' : 'completed';
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
            }
        }
    }
}
