<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use Illuminate\View\View;
use App\Models\Notification;

class NotificationController extends WebController
{
    /**
     * Display a list of all notifications.
     *
     * @return View
     */
    public function index(): View
    {
        $notifications = Notification::latest()->paginate(20);

        return view('web.admin.notifications.index', compact('notifications'));
    }
}
