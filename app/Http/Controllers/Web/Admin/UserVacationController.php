<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Support\Carbon;

class UserVacationController extends WebController
{
    /**
     * Displays a user's vacation calendar.
     *
     * @param User $user
     * @return View|RedirectResponse
     */
    public function index(User $user): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($user) {
            $vacationDates = $user->vacations()
                ->pluck('date')
                ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
                ->toArray();

            return view('web.admin.users.vacations', compact('user', 'vacationDates'));
        }, route('admin.users.index'));
    }

    /**
     * Stores the selected holidays for a user.
     *
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $user) {
            $dates = json_decode($request->input('dates_json', '[]'), true);

            $user->vacations()->delete();

            foreach ($dates as $date) {
                $user->vacations()->create(['date' => $date]);
            }

            return redirect()->route('admin.users.vacations', $user->id);
        }, route('admin.users.index'), 'Vacaciones creadas correctamente.');
    }

}
