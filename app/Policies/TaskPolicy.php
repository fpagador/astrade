<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given user can view the task.
     *
     * @param  User  $user
     * @param  Task  $task
     * @return bool
     */
    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->user_id || $user->hasPermission('view_task');
    }

    /**
     * Determine if the given user can create the task.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_task');
    }

    /**
     * Determine if the given user can update the task.
     *
     * @param  User  $user
     * @param  Task  $task
     * @return bool
     */
    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->user_id || $user->hasPermission('edit_task');
    }

    /**
     * Determine if the user can delete the task.
     *
     * @param  User  $user
     * @param User $model
     * @return bool
     */
    public function delete(User $user, User $model): bool
    {
        return $user->hasPermission('delete_task');
    }

    /**
     * Allow Admins to do everything automatically
     * @param  User  $user
     * @return bool
     */
    public function before(User $user): bool
    {
        return $user->roles->contains('role_name', 'admin') ? true : false;
    }
}
