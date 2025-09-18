<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Requests\Admin\StoreOrUpdateCompanyRequest;

class CompanyController extends WebController
{

    /**
     * Construct
     *
     * @param CompanyService $companyService
     */
    public function __construct(
        protected CompanyService $companyService,
    ) {}

    /**
     * Display a paginated list of companies with optional filters.
     *
     * @param Request $request
     * @return View | RedirectResponse
     */
    public function index(Request $request): View | RedirectResponse
    {
        return $this->tryCatch(function () use ( $request) {
            $filters = $request->only(['name', 'address']);
            $sort = $request->get('sort', 'name');
            $direction = $request->get('direction', 'asc');
            $companies = $this->companyService->getCompanies($filters, $sort, $direction);

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
            $this->companyService->createCompany($request->validated(), $request->input('phones'));

            return redirect()->route('admin.companies.index');
        }, route('admin.companies.create'), 'La Empresa se ha creado correctamente.');
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
            $this->companyService->updateCompany($company, $request->validated(), $request->input('phones'));

            return redirect()->route('admin.companies.index');
        }, route('admin.companies.create'), 'La Empresa se ha actualizado correctamente.');
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
            $this->companyService->deleteCompany($company);

            return redirect()->route('admin.companies.index');
        }, route('admin.companies.index'), 'La Empresa se ha eliminado correctamente.');
    }

}
