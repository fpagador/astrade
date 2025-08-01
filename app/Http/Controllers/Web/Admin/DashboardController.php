<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;

class DashboardController extends WebController
{
    public function index()
    {
        return view('web.admin.dashboard');
    }
}
