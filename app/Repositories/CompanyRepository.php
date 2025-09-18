<?php

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository class for handling database interactions related to the Company entity.
 */
class CompanyRepository
{
    /**
     * Get paginated list of companies with optional filters and sorting.
     *
     * @param array $filters
     * @param string $sort
     * @param string $direction
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(
        array $filters = [],
        string $sort = 'name',
        string $direction = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator
    {
        $query = Company::query();

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['address'])) {
            $query->where('address', 'like', '%' . $filters['address'] . '%');
        }

        // Define sortable columns
        $sortableColumns = ['name', 'address', 'description'];
        if (in_array($sort, $sortableColumns)) {
            $query->orderBy($sort, $direction);
        }

        // Default fallback ordering
        $query->orderBy('id', 'desc');

        return $query->paginate($perPage)->appends($filters);
    }

    /**
     * Create a new company record.
     *
     * @param array $data
     * @return Company
     */
    public function create(array $data): Company
    {
        return Company::create($data);
    }

    /**
     * Update a given company.
     *
     * @param Company $company
     * @param array $data
     * @return Company
     */
    public function update(Company $company, array $data): Company
    {
        $company->update($data);
        return $company;
    }

    /**
     * Delete a given company.
     *
     * @param Company $company
     * @return void
     */
    public function delete(Company $company): void
    {
        $company->delete();
    }

    /**
     * Get all companies.
     *
     * @return Collection<int, Company>
     */
    public function getAll(): Collection
    {
        return Company::orderBy('name')->get();
    }
}
