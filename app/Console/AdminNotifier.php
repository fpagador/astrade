<?php

namespace App\Console;

use Illuminate\Notifications\Notifiable;

class AdminNotifier
{
    use Notifiable;

    public function routeNotificationForMail(): string
    {
        return env('ADMIN_EMAIL');
    }
}
