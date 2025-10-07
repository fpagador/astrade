<?php

namespace App\Repositories;

use App\Enums\CalendarStatus;
use App\Enums\TaskStatus;
use App\Models\User;
use App\Models\WorkCalendarTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Enums\UserTypeEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Repository class for handling database interactions related to the User entity.
 */
class UserRepository
{
    /**
     * Get the authenticated user with related entities loaded.
     *
     * @param int $userId
     * @return User|null
     */
    public function findWithProfileData(int $userId): ?User
    {
        return User::with([
            'role:id,role_name',
            'company.phones',
        ])->find($userId);
    }

    /**
     * Update the FCM token for the given user.
     *
     * @param User $user
     * @param string $fcmToken
     * @return User
     */
    public function updateFcmToken(User $user, string $fcmToken): User
    {
        $user->fcm_token = $fcmToken;
        $user->save();

        return $user;
    }

    /**
     * Delete the FCM token for the given user.
     *
     * @param User $user
     * @return User
     */
    public function deleteFcmToken(User $user): User
    {
        $user->fcm_token = null;
        $user->save();

        return $user;
    }

    /**
     * Get phone numbers of users that can receive calls.
     *
     * @return array
     */
    public function getActivePhones(): array
    {
        $phones = User::where('can_be_called', true)
            ->pluck('phone')
            ->map(function ($phone) {
                return $phone ? preg_replace('/[^\d+]/', '', $phone) : null;
            })
            ->filter()
            ->values()
            ->toArray();

        if (!$phones) {
            throw new ModelNotFoundException("No users with configured phones have been found.");
        }

        return $phones;
    }

    /**
     * Paginate the base query of users.
     *
     * @param array $filters
     * @param string $type
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateUsers(string $type, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->queryUsers($type, $filters)->paginate($perPage)->appends($filters);
    }

    /**
     * Build a query for users with optional filters, relations and sorting.
     *
     * @param array $filters
     * @param string $type
     * @return Builder
     */
    public function queryUsers(string $type, array $filters = [] ): Builder
    {
        $query = User::query()->with('role');

        $query->select('users.*')
            ->selectRaw('(CASE WHEN company_id IS NULL OR work_calendar_template_id IS NULL THEN 1 ELSE 0 END) as has_warning');

        // Filter by type
        if ($type === UserTypeEnum::MOBILE->value) {
            $query->whereHas('role', fn($q) => $q->where('role_name', RoleEnum::USER->value));
        } else {
            $query->whereHas('role', fn($q) =>
            $q->whereIn('role_name', [RoleEnum::ADMIN->value, RoleEnum::MANAGER->value])
            );
        }

        // Apply filters
        foreach ($filters as $field => $value) {
            if ($value === null || $value === '') continue;

            if ($field === 'role') {
                $query->whereHas('role', fn($q) => $q->where('role_name', $value));
            } elseif ($field === 'company_id') {
                $query->where('company_id', $value);
            } elseif (in_array($field, ['name', 'email', 'dni'])) {
                $query->where($field, 'like', "%{$value}%");
            }
        }

        // Sorting
        $sort = $filters['sort'] ?? 'name';
        $direction = $filters['direction'] ?? 'asc';

        $sortableColumns = ['name', 'surname', 'dni', 'email', 'phone', 'can_be_called'];
        $sortableRelations = [
            'role'    => ['table' => 'roles', 'local_key' => 'role_id', 'foreign_key' => 'id', 'column' => 'role_name'],
            'company' => ['table' => 'companies', 'local_key' => 'company_id', 'foreign_key' => 'id', 'column' => 'name'],
        ];

        if ($sort === 'has_warning') {
            $query->orderByRaw('(CASE WHEN company_id IS NULL OR work_calendar_template_id IS NULL THEN 1 ELSE 0 END) ' . $direction);
        } elseif (in_array($sort, $sortableColumns)) {
            $query->orderBy("users.$sort", $direction);
        } elseif (array_key_exists($sort, $sortableRelations)) {
            $rel = $sortableRelations[$sort];
            $query->leftJoin($rel['table'], "{$rel['table']}.{$rel['foreign_key']}", '=', "users.{$rel['local_key']}")
                ->orderBy("{$rel['table']}.{$rel['column']}", $direction)
                ->select('users.*');
        } else {
            // fallback
            $query->orderBy('users.name', 'asc');
        }

        return $query;
    }

    /**
     * Count users with can_be_called = true for Admin/Manager roles.
     *
     * @return int
     */
    public function countCallableManagers(): int
    {
        $roles = [RoleEnum::ADMIN->value, RoleEnum::MANAGER->value];

        return User::where('can_be_called', true)
            ->whereHas('role', fn($q) => $q->whereIn('role_name', $roles))
            ->count();
    }

    /**
     * Find a user by ID.
     *
     * @param int $id
     * @return User
     */
    public function find(int $id): User
    {
        return User::findOrFail($id);
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Update a user.
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user;
    }

    /**
     * Delete a user.
     *
     * @param User $user
     * @return void
     */
    public function delete(User $user): void
    {
        $user->delete();
    }

    /**
     * Get all users (for company filter & search)
     *
     * @return Collection
     */
    public function getAllUsersForCompany (): Collection
    {
        return User::select('id', 'name', 'surname', 'company_id')
            ->with('company:id,name')
            ->orderBy('surname')
            ->orderBy('name')
            ->get();
    }

    /**
     * Assign a work calendar template to multiple users.
     *
     * @param array $userIds
     * @param int $templateId
     * @return int
     */
    public function assignTemplateToUsers(array $userIds, int $templateId): int
    {
        return User::whereIn('id', $userIds)
            ->update(['work_calendar_template_id' => $templateId]);
    }

    /**
     * Count all users.
     *
     * @return int
     */
    public function countAll(): int
    {
        return User::count();
    }

    /**
     * Count users without assigned work calendar.
     *
     * @return int
     */
    public function countWithoutCalendar(): int
    {
        return User::whereNull('work_calendar_template_id')->count();
    }

    /**
     * Count users by role names.
     *
     * @param array $roles
     * @return int
     */
    public function countByRoles(array $roles): int
    {
        return User::whereHas('role', fn($q) => $q->whereIn('role_name', $roles))->count();
    }

    /**
     * Count users with pending tasks.
     *
     * @return int
     */
    public function countWithPendingTasks(): int
    {
        return User::whereHas('tasks', fn($q) => $q->where('status', TaskStatus::PENDING->value))->count();
    }

    /**
     * Get users without any tasks.
     *
     * @return Collection
     */
    public function getUsersWithoutTasks(): Collection
    {
        return User::doesntHave('tasks')->get(['id', 'name', 'surname']);
    }

    /**
     * Group users by company and count.
     *
     * @return Collection
     */
    public function getUsersGroupedByCompany(): Collection
    {
        return User::selectRaw('company_id, COUNT(*) as total')
            ->groupBy('company_id')
            ->with('company:id,name')
            ->get();
    }

    /**
     * Delete users from the work calendar template with inactive status
     *
     * @param WorkCalendarTemplate $template
     * @return void
     */
    public function deleteWorkCalendarTemplateFromUsers(WorkCalendarTemplate $template): void
    {
        if ($template->status === CalendarStatus::INACTIVE->value) {
            User::where('work_calendar_template_id', $template->id)
                ->update(['work_calendar_template_id' => null]);
        }
    }

    /**
     * Gets users who have a company or those who do not have one.
     *
     * @return Collection
     */
    public function getUsersByCompany(?int $companyId): Collection
    {
        if (is_null($companyId)) {
            return User::whereNull('company_id')
                ->get(['name', 'surname']);
        } else {
            return User::where('company_id', $companyId)
                ->get(['name', 'surname']);
        }
    }
}
