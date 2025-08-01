<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use Illuminate\View\View;

class CalendarController extends WebController
{
    /**
     * Display the calendar view.
     *
     * @return View
     */
    public function index(): View
    {
        return view('web.admin.calendar.index');
    }
}
