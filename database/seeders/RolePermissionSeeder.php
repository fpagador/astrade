<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['admin', 'manager', 'user'];

        $permissions = [
            ['code' => 'create_user', 'description' => 'Crear usuarios'],
            ['code' => 'view_user', 'description' => 'Ver usuarios'],
            ['code' => 'edit_user', 'description' => 'Editar usuarios'],
            ['code' => 'delete_user', 'description' => 'Eliminar usuarios'],

            ['code' => 'create_task', 'description' => 'Crear tareas'],
            ['code' => 'view_task', 'description' => 'Ver tareas'],
            ['code' => 'edit_task', 'description' => 'Editar tareas'],
            ['code' => 'delete_task', 'description' => 'Eliminar tareas'],

            ['code' => 'change_password', 'description' => 'Cambiar contraseña de otros usuarios'],
            ['code' => 'view_logs', 'description' => 'Ver tabla de logs'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['code' => $perm['code']],
                ['description' => $perm['description']]
            );
        }

        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['role_name' => $roleName]);

            if ($roleName === 'admin') {
                $role->permissions()->sync(Permission::pluck('id'));
            }

            if ($roleName === 'manager') {
                $role->permissions()->sync(
                    Permission::whereIn('code', [
                        'create_user', 'view_user', 'edit_user',
                        'view_task', 'edit_task', 'change_password', 'delete_task'
                    ])->pluck('id')
                );
            }
        }
    }
}
