<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Task;
use App\Models\Subtask;
use App\Models\Company;
use App\Models\CompanyTask;
use App\Models\CompanyPhone;
use App\Models\TaskCompletionLog;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        $password = 'TestPass2025!';

        // Crear solo si no existen
        if (Company::count() < 3) {
            $companies = Company::factory()->count(3)->create();
        } else {
            $companies = Company::all()->take(3);
        }

        // Crear 20 usuarios con contraseña y empresa asignada
        $users = collect();
        for ($i = 0; $i < 20; $i++) {
            $users->push(User::factory()->create([
                'company_id' => null, // por defecto null
                'password' => Hash::make($password),
                'role_id' => 3,       // asignar user como rol inicial
            ]));
        }

        // Usuario admin fijo
        $admin = $users->first();
        $admin->update([
            'dni' => '01035080L',
            'role_id' => 1,
            'company_id' => null, // Admin sin empresa
        ]);

        // Resto de usuarios: manager o user
        foreach ($users->skip(1) as $user) {
            $newRole = rand(2, 3);
            $companyId = ($newRole === 3) ? $companies->random()->id : null; // solo user tiene empresa

            $user->update([
                'role_id' => $newRole,
                'company_id' => $companyId,
            ]);
        }

        // Usuarios tipo "user"
        $userUsers = $users->where('role_id', 3)->values();

        // Asociar teléfonos a empresas
        foreach ($companies as $index => $company) {
            // Añadir teléfonos a empresa
            $phoneCount = rand(1, 2);
            for ($i = 0; $i < $phoneCount; $i++) {
                CompanyPhone::create([
                    'company_id' => $company->id,
                    'phone_number' => fake()->phoneNumber(),
                    'name' => fake()->optional()->words(2, true),
                ]);
            }
        }

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
                CompanyTask::create([
                    'task_id' => $task->id,
                    'company_id' => $user->company_id,
                ]);
            }
        }
    }
}
