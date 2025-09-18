<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Log;

class LogController extends WebController
{
    /**
     * Constructor
     *
     * @param LogService $logService
     */
    public function __construct(
        protected LogService $logService,
    ) {}

    /**
     * Display a paginated list of logs with optional filters.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $this->authorize('viewLogs', Log::class);

        return $this->tryCatch(function () use ($request) {
            $filters = $request->only(['date_from', 'date_to', 'level', 'message']);
            $logs = $this->logService->getLogs($filters);
            $levels = $this->logService->getLogLevels();

            return view('web.admin.logs.index', compact('logs', 'levels'));
        });
    }
}
