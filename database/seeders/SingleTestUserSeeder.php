<?php

namespace Database\Seeders;

use App\Enums\NotificationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SingleTestUserSeeder extends Seeder
{
    public function run()
    {
        $password = 'TestPass2025!';

        User::create([
            'name' => 'Usuario',
            'surname' => 'Prueba',
            'dni' => '01035080L',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => Hash::make($password),
            'role_id' => 1,
            'company_id' => null,
            'work_calendar_template_id' => null,
            'notification_type' => NotificationType::NONE->value,
            'can_receive_notifications' => false,
        ]);
    }
}
