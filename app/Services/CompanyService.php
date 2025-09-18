<?php

namespace App\Services;

use App\Models\Company;
use App\Repositories\CompanyRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service class for business logic related to companies.
 */
class CompanyService
{

    /**
     * CompanyService constructor.
     *
     * @param CompanyRepository $companyRepository
     */
    public function __construct(
        protected CompanyRepository $companyRepository
    ) {}

    /**
     * Get paginated list of companies with filters and sorting.
     *
     * @param array $filters
     * @param string $sort
     * @param string $direction
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getCompanies(array $filters = [], string $sort = 'name', string $direction = 'asc', int $perPage = 15): LengthAwarePaginator
    {
        return $this->companyRepository->paginate($filters, $sort, $direction, $perPage);
    }

    /**
     * Create a new company and optionally add phones.
     *
     * @param array $data
     * @param array|null $phones
     * @return Company
     */
    public function createCompany(array $data, ?array $phones = null): Company
    {
        $company = $this->companyRepository->create($data);

        if (!empty($phones)) {
            $this->savePhones($company, $phones);
        }

        return $company;
    }

    /**
     * Update an existing company and optionally update phones.
     *
     * @param Company $company
     * @param array $data
     * @param array|null $phones
     * @return Company
     */
    public function updateCompany(Company $company, array $data, ?array $phones = null): Company
    {
        $company = $this->companyRepository->update($company, $data);

        if (!empty($phones)) {
            $this->savePhones($company, $phones);
        }

        return $company;
    }

    /**
     * Delete a company.
     *
     * @param Company $company
     * @return void
     */
    public function deleteCompany(Company $company): void
    {
        $this->companyRepository->delete($company);
    }

    /**
     * Save phones for a given company.
     *
     * @param Company $company
     * @param array $phones
     * @return void
     */
    protected function savePhones(Company $company, array $phones): void
    {
        // Filter out empty phone entries
        $phonesData = array_filter($phones, fn($phone) => !empty($phone['name']) || !empty($phone['phone_number']));

        // Delete existing phones
        $company->phones()->delete();

        // Insert new phones
        foreach ($phonesData as $phone) {
            $company->phones()->create([
                'name' => $phone['name'] ?? null,
                'phone_number' => $phone['phone_number'] ?? null,
            ]);
        }
    }
}
