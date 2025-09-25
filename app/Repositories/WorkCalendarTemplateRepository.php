<?php

namespace App\Repositories;

use App\Enums\CalendarType;
use App\Models\WorkCalendarTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\CalendarStatus;

/**
 * Repository class for handling Work Calendar Template data persistence.
 */
class WorkCalendarTemplateRepository
{
    /**
     * Get Work Calendar Template By ID
     *
     * @param int $templateId
     * @return WorkCalendarTemplate
     */
    public function getWorkCalendarTemplateById(int $templateId): WorkCalendarTemplate
    {
       return WorkCalendarTemplate::find($templateId);
    }

    /**
     * Build base query for calendar templates with filters.
     *
     * @param array $filters
     * @return Builder
     */
    public function query(array $filters = []): Builder
    {
        $query = WorkCalendarTemplate::withCount([
            'days as holidays_count' => fn($q) => $q->where('day_type', CalendarType::HOLIDAY->value)
        ]);

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * Paginate calendar templates with filters and sorting.
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
        $sortableColumns = ['name', 'year', 'status'];

        $query = $this->query($filters);

        if (in_array($sort, $sortableColumns)) {
            $query->orderBy("work_calendar_templates.$sort", $direction);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new work calendar template.
     *
     * @param array $data
     * @return WorkCalendarTemplate
     */
    public function create(array $data): WorkCalendarTemplate
    {
        return WorkCalendarTemplate::create($data);
    }

    /**
     * Update an existing work calendar template.
     *
     * @param WorkCalendarTemplate $template
     * @param array $data
     * @return bool
     */
    public function update(WorkCalendarTemplate $template, array $data): bool
    {
        return $template->update($data);
    }

    /**
     * Delete a Work Calendar Template.
     *
     * @param WorkCalendarTemplate $template
     * @return bool|null
     */
    public function delete(WorkCalendarTemplate $template): ?bool
    {
        return $template->delete();
    }

    /**
     * Retrieve all active Work Calendar Templates.
     *
     * @return Collection
     */
    public function getActive(): Collection
    {
        return WorkCalendarTemplate::where('status', CalendarStatus::ACTIVE->value)->get();
    }

    /**
     * Count all WorkCalendarTemplate.
     *
     * @return int
     */
    public function countActive(): int
    {
        return WorkCalendarTemplate::where('status', CalendarStatus::ACTIVE->value)->count();
    }

    /**
     * Count all WorkCalendarTemplate.
     *
     * @param int $year
     * @return WorkCalendarTemplate
     */
    public function getActiveTemplateForYear(int $year): WorkCalendarTemplate
    {
        return WorkCalendarTemplate::where('year', $year)
            ->where('status', CalendarStatus::ACTIVE->value)
            ->first();
    }

}
