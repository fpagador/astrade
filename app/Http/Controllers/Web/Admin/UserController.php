<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\WebController;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Models\Role;

class UserController extends WebController
{
    /**
     * Display a paginated list of users with optional filters.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function index(Request $request): View|RedirectResponse
    {
        return $this->tryCatch(function () use ( $request) {
            $query = User::query()->with('role');

            if ($request->filled('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            if ($request->filled('dni')) {
                $query->where('dni', 'like', '%' . $request->dni . '%');
            }

            if ($request->filled('email')) {
                $query->where('email', 'like', '%' . $request->email . '%');
            }

            if ($request->filled('role')) {
                $query->whereHas('role', function ($q) use ($request) {
                    $q->where('role_name', $request->role);
                });
            }

            $users = $query->paginate(15);

            return view('web.admin.users.index', compact('users'));
        });
    }

    /**
     * Show the form for creating a new user.
     *
     * @return View
     */
    public function create(): View
    {
        $authUser = auth()->user();

        $assignableRoleNames = match (true) {
            $authUser->hasRole('admin') => ['manager', 'user'],
            $authUser->hasRole('manager') => ['user'],
            default => [],
        };

        $assignableRoles = Role::whereIn('role_name', $assignableRoleNames)->get();

        return view('web.admin.users.create', compact('assignableRoles'));
    }

    /**
     * Store a newly created user in storage.
     *
     * @param StoreUserRequest $request
     * @return View|RedirectResponse
     */
    public function store(StoreUserRequest $request): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($request) {
            $validated = $request->validated();
            $authUser = auth()->user();

            // Determine roles that the authenticated user can assign
            $allowedRoleNames = match (true) {
                $authUser->hasRole('admin') => ['manager', 'user'],
                $authUser->hasRole('manager') => ['user'],
                default => [],
            };

            $role = Role::findOrFail($request->role_id);

            // Security: Do not allow assigning an unauthorized role
            if (!in_array($role->role_name, $allowedRoleNames)) {
                return redirect()->route('admin.users.create')->with('error', 'No puedes asignar ese rol.');
            }

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('users', 'public');
                $validated['photo'] = $photoPath;
            }

            $validated['password'] = bcrypt($validated['password']);
            $validated['can_receive_notifications'] = $request->has('can_receive_notifications');
            $validated['role_id'] = $request->role_id;

            User::create($validated);

            return redirect()->route('admin.users.index');
        }, route('admin.users.create'), 'Usuario creado correctamente.');
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param int $id
     * @return View|RedirectResponse
     */
    public function edit(int $id): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($id) {
            $authUser = auth()->user();

            $assignableRoleNames = match (true) {
                $authUser->hasRole('admin') => ['manager', 'user'],
                $authUser->hasRole('manager') => ['user'],
                default => [],
            };

            $assignableRoles = Role::whereIn('role_name', $assignableRoleNames)->get();

            $user = User::findOrFail($id);
            return view('web.admin.users.edit', compact('user','assignableRoles'));
        }, route('admin.users.index'));
    }

    /**
     * Update the specified user in storage.
     *
     * @param UpdateUserRequest $request
     * @param int $id
     * @return View|RedirectResponse
     */
    public function update(UpdateUserRequest $request, int $id): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $id) {
            $user = User::findOrFail($id);
            $validated = $request->validated();

            if ($request->hasFile('photo')) {
                if ($user->photo) {
                    Storage::disk('public')->delete($user->photo);
                }
                $validated['photo'] = $request->file('photo')->store('users', 'public');
            }

            if ($request->filled('password')) {
                $validated['password'] = bcrypt($request->password);
            } else {
                unset($validated['password']);
            }

            $user->update($validated);

            return redirect()->route('admin.users.index');
        }, route('admin.users.index'), 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified user from storage.
     *
     * @param int $id
     * @return View|RedirectResponse
     */
    public function destroy(int $id): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($id) {
            $user = User::findOrFail($id);
            $this->authorize('delete', $user);

            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            $user->delete();

            return redirect()->route('admin.users.index');
        }, route('admin.users.index'), 'Usuario eliminado correctamente.');
    }

    /**
     * Show the form for editing the user's password.
     *
     * @param User $user
     * @return View
     */
    public function editPassword(User $user): View
    {
        $this->authorize('changePassword', $user);
        return view('web.admin.users.edit-password', compact('user'));
    }

    /**
     * Update the specified user's password.
     *
     * @param UpdateUserPasswordRequest $request
     * @param User $user
     * @return RedirectResponse
     */
    public function updatePassword(UpdateUserPasswordRequest $request, User $user): RedirectResponse
    {
        $this->authorize('changePassword', $user);
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Contraseña actualizada correctamente.');
    }
}
