<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Services\DashboardService;

class DashboardController extends WebController
{
    /**
     * DashboardController constructor.
     *
     * @param DashboardService $dashboardService

     */
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index()
    {
        return $this->tryCatch(function () {
            $data = $this->dashboardService->getDashboardData();
            return view('web.admin.dashboard', $data);
        });
    }
}
