<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Requests\Admin\StoreOrUpdateLocationRequest;

class LocationController extends WebController
{
    /**
     * Display a paginated list of locations with optional filters.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        return $this->tryCatch(function () use ( $request) {
            $query = Location::query();

            if ($request->filled('name')) {
                $query->where('name', 'like', '%'.$request->name.'%');
            }

            if ($request->filled('address')) {
                $query->where('address', 'like', '%'.$request->address.'%');
            }

            $locations = $query->orderBy('id', 'desc')->paginate(15);

            return view('web.admin.locations.index', compact('locations'));
        });
    }

    /**
     * Show the form for creating a new location.
     *
     * @return View
     */
    public function create(): View
    {
        return view('web.admin.locations.create');
    }

    /**
     * Store a newly created location in the database.
     *
     * @param StoreOrUpdateLocationRequest $request
     * @return RedirectResponse
     */
    public function store(StoreOrUpdateLocationRequest $request): RedirectResponse
    {
        return $this->tryCatch(function () use ( $request) {
            $validated = $request->validated();
            Location::create($validated);

            return redirect()->route('admin.locations.index');
        }, route('admin.locations.create'), 'Ubicación creada correctamente.');
    }

    /**
     * Show the form for editing the specified location.
     *
     * @param Location $location
     * @return View
     */
    public function edit(Location $location): View
    {
        return view('web.admin.locations.edit', compact('location'));
    }

    /**
     * Update the specified location in the database.
     *
     * @param StoreOrUpdateLocationRequest $request
     * @param Location $location
     * @return RedirectResponse
     */
    public function update(StoreOrUpdateLocationRequest $request, Location $location): RedirectResponse
    {
        return $this->tryCatch(function () use ( $request, $location) {
            $validated = $request->validated();
            $location->update($validated);

            return redirect()->route('admin.locations.index');
        }, route('admin.locations.create'), 'Ubicación actualizada correctamente.');
    }

    /**
     * Remove the specified location from the database.
     *
     * @param Location $location
     * @return RedirectResponse
     */
    public function destroy(Location $location): RedirectResponse
    {
        return $this->tryCatch(function () use ( $location) {
            $location->delete();

            return redirect()->route('admin.locations.index');
        }, route('admin.locations.index'), 'Ubicación eliminada correctamente.');
    }

}
