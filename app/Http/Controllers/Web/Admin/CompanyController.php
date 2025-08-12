<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Requests\Admin\StoreOrUpdateCompanyRequest;

class CompanyController extends WebController
{
    /**
     * Display a paginated list of companies with optional filters.
     *
     * @param Request $request
     * @return View | RedirectResponse
     */
    public function index(Request $request): View | RedirectResponse
    {
        return $this->tryCatch(function () use ( $request) {
            $query = Company::query();

            if ($request->filled('name')) {
                $query->where('name', 'like', '%'.$request->name.'%');
            }

            if ($request->filled('address')) {
                $query->where('address', 'like', '%'.$request->address.'%');
            }

            $companies = $query->orderBy('id', 'desc')->paginate(15);

            return view('web.admin.companies.index', compact('companies'));
        });
    }

    /**
     * Show the form for creating a new company.
     *
     * @return View
     */
    public function create(): View
    {
        return view('web.admin.companies.create');
    }

    /**
     * Store a newly created company in the database.
     *
     * @param StoreOrUpdateCompanyRequest $request
     * @return RedirectResponse
     */
    public function store(StoreOrUpdateCompanyRequest $request): RedirectResponse
    {
        return $this->tryCatch(function () use ( $request) {
            $validated = $request->validated();
            $company = Company::create($validated);
            $this->addPhones($request, $company);

            return redirect()->route('admin.companies.index');
        }, route('admin.companies.create'), 'Ubicación creada correctamente.');
    }

    /**
     * Show the form for editing the specified company.
     *
     * @param Company $company
     * @return View
     */
    public function edit(Company $company): View
    {
        return view('web.admin.companies.edit', compact('company'));
    }

    /**
     * Update the specified company in the database.
     *
     * @param StoreOrUpdateCompanyRequest $request
     * @param Company $company
     * @return RedirectResponse
     */
    public function update(StoreOrUpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        return $this->tryCatch(function () use ( $request, $company) {
            $validated = $request->validated();
            $company->update($validated);
            $this->addPhones($request, $company);

            return redirect()->route('admin.companies.index');
        }, route('admin.companies.create'), 'Ubicación actualizada correctamente.');
    }

    /**
     * Remove the specified company from the database.
     *
     * @param Company $company
     * @return RedirectResponse
     */
    public function destroy(Company $company): RedirectResponse
    {
        return $this->tryCatch(function () use ( $company) {
            $company->delete();

            return redirect()->route('admin.companies.index');
        }, route('admin.companies.index'), 'Ubicación eliminada correctamente.');
    }

    public function addPhones(StoreOrUpdateCompanyRequest $request, Company $company)
    {
        if ($request->has('phones')) {
            $phonesData = array_filter($request->input('phones'), function($phone) {
                return !empty($phone['name']) || !empty($phone['phone_number']);
            });

            $company->phones()->delete();
            foreach ($phonesData as $phone) {
                $company->phones()->create([
                    'name' => $phone['name'] ?? null,
                    'phone_number' => $phone['phone_number'] ?? null,
                ]);
            }
        }
    }

}
