<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Log;

class LogController extends WebController
{
    /**
     * Display a list of logs.
     *
     * @return View
     */
    public function index(Request $request): View
    {
        $this->authorize('viewLogs', Log::class);

        return $this->tryCatch(function () use ( $request) {
            // Base query
            $query = Log::query();

            // Filter by date from
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            // Filter by date to
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            // Filter by type
            if ($request->filled('level')) {
                $query->where('level', $request->level);
            }

            // Filter by message
            if ($request->filled('message')) {
                $query->where('message', 'like', '%' . $request->message . '%');
            }

            //Get unique types for the dropdown
            $levels = Log::select('level')
                ->distinct()
                ->orderBy('level')
                ->pluck('level');

            //Pagination and descending order (most recent first)
            $logs = $query->orderByDesc('id')->paginate(20)->withQueryString();

            return view('web.admin.logs.index', compact('logs', 'levels'));
        });
    }
}
