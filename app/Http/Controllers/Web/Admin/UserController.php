<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\RoleEnum;
use App\Enums\UserTypeEnum;
use App\Http\Controllers\Web\WebController;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Models\WorkCalendarTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Models\Role;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;

class UserController extends WebController
{
    /**
     * Display a paginated list of manager users with optional filters.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function index(Request $request): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($request) {
            auth()->user();
            $type = $request->get('type'); // 'mobile' or 'management'

            $query = User::query()->with('role');

            if ($type === UserTypeEnum::mobile->value) {
                // Mobile users only
                $query->whereHas('role', fn($q) => $q->where('role_name', 'user'));
            } else {
                // By default 'management' for users with admin role
                $this->authorize('viewAdmin', User::class);
                $query->whereHas('role', fn($q) =>
                $q->whereIn('role_name', ['admin', 'manager'])
                );
            }

            //Common filters
            if ($request->filled('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            if ($request->filled('email')) {
                $query->where('email', 'like', '%' . $request->email . '%');
            }

            if ($request->filled('dni')) {
                $query->where('dni', 'like', '%' . $request->dni . '%');
            }

            if ($request->filled('role')) {
                $query->whereHas('role', function ($q) use ($request) {
                    $q->where('role_name', $request->role);
                });
            }

            if ($request->filled('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            $users = $query->paginate(15)->appends($request->query());

            //Dynamic view by type
            if ($type === UserTypeEnum::mobile->value) {
                $companies = Company::orderBy('name')->get();
                return view('web.admin.users.mobile', compact('users', 'companies'));
            }

            return view('web.admin.users.index', compact('users'));
        });
    }

    /**
     * Show the user profile.
     *
     * @param User $user
     * @param Request $request
     * @return View
     */
    public function show(User $user, Request $request): View
    {
        $type = $request->get('type', UserTypeEnum::mobile->value);

        return view('web.admin.users.show', compact('user', 'type'));
    }

    /**
     * Show the form for creating a new user.
     *
     * @param Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        $authUser = auth()->user();

        // If you come from the mobile users view (passed role=user)
        if ($request->input('role') === RoleEnum::user->value ) {
            $assignableRoleNames = ['user'];
        } else {
            $assignableRoleNames = match (true) {
                $authUser->hasRole('admin') => ['admin', 'manager'],
                $authUser->hasRole('manager') => ['user'],
                default => [],
            };
        }

        $assignableRoles = Role::whereIn('role_name', $assignableRoleNames)->get();
        $companies = Company::all();
        $workCalendarTemplante = WorkCalendarTemplate::orderBy('year', 'desc')->get();

        $defaultRole = null;

        if ($request->filled('role')) {
            $defaultRole = Role::where('role_name', $request->role)->first()?->id;
        }

        return view('web.admin.users.create', compact(
            'assignableRoles',
            'companies',
            'defaultRole',
            'workCalendarTemplante'
            )
        );
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
                $authUser->hasRole('admin') => ['admin', 'manager', 'user'],
                $authUser->hasRole('manager') => ['user'],
                default => [],
            };

            $role = Role::findOrFail($request->role_id);

            // Security: Do not allow assigning an unauthorized role
            if (!in_array($role->role_name, $allowedRoleNames)) {
                return redirect()->route('admin.users.create')->with('error', 'No puedes asignar ese rol.');
            }

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('photos', 'public');
                $validated['photo'] = $photoPath;
            }

            $validated['password'] = bcrypt($validated['password']);

            $validated['can_receive_notifications'] = $request->has('can_receive_notifications');
            if (!$validated['can_receive_notifications']) {
                $validated['notification_type'] = 'none';
            }
            $validated['role_id'] = $request->role_id;

            User::create($validated);
            $type = $role->role_name === RoleEnum::user->value ? UserTypeEnum::mobile->value : UserTypeEnum::management->value;

            return redirect()->route('admin.users.index', ['type' => $type]);
        }, route('admin.users.create'), 'Usuario creado correctamente.');
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param Request $request
     * @param int $id
     * @return View|RedirectResponse
     */
    public function edit(Request $request, int $id): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($request, $id) {
            $authUser = auth()->user();

            $assignableRoleNames = match (true) {
                $authUser->hasRole('admin') => ['admin','manager', 'user'],
                $authUser->hasRole('manager') => ['user'],
                default => [],
            };

            $assignableRoles = Role::whereIn('role_name', $assignableRoleNames)->get();

            $user = User::findOrFail($id);

            $defaultRole = $request->role
                ? Role::where('role_name', $request->role)->first()?->id
                : $user->role_id;

            $companies = Company::all();
            $workCalendarTemplante = WorkCalendarTemplate::orderBy('year', 'desc')->get();

            return view('web.admin.users.edit', compact(
                'user',
                'assignableRoles',
                'defaultRole',
                'companies',
                'workCalendarTemplante'
            ));
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
                $validated['photo'] = $request->file('photo')->store('photos', 'public');
            }

            if ($request->filled('password')) {
                $validated['password'] = bcrypt($request->password);
            } else {
                unset($validated['password']);
            }

            $user->update($validated);

            $role = Role::findOrFail($request->input('role_id'));
            $type = $role->role_name === RoleEnum::user->value ? UserTypeEnum::mobile->value : UserTypeEnum::management->value;

            return redirect()->route('admin.users.index', ['type' => $type]);
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

    /**
     * Validate a single field from the request using StoreUserRequest rules.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateField(Request $request): JsonResponse
    {
        try {
            $field = $request->input('field');
            $value = $request->input('value');

            // Usa el FormRequest para obtener reglas y mensajes
            /** @var StoreUserRequest $formRequest */
            $formRequest = new StoreUserRequest();
            $rules = $formRequest->rules();
            $messages = $formRequest->messages();

            // Prepara datos base
            $allFields = array_keys($rules);
            $requestData = $request->only($allFields);
            $requestData[$field] = $value;

            // If validating password or confirmation, ensure both are present
            if (in_array($field, ['password', 'password_confirmation'])) {
                $requestData['password'] = $request->input('password');
                $requestData['password_confirmation'] = $request->input('password_confirmation');
            }

            // Reglas por campo
            if ($field === 'password') {
                // Toma las reglas de password pero SIN 'confirmed'
                $pwdRules = $rules['password'];
                if (is_array($pwdRules)) {
                    $pwdRules = array_values(array_filter($pwdRules, function ($r) {
                        if (is_string($r)) return $r !== 'confirmed';
                        return !($r);
                    }));
                }
                $singleRule = ['password' => $pwdRules];
            } elseif ($field === 'password_confirmation') {
                // Solo validar que coincida con password
                $singleRule = ['password_confirmation' => ['same:password']];
            } else {
                $singleRule = [$field => $rules[$field] ?? []];
            }

            // Mensajes solo del campo que se valida
            $singleMessages = array_filter($messages, function ($key) use ($field) {
                return str_starts_with($key, $field . '.');
            }, ARRAY_FILTER_USE_KEY);

            // Asegura mensaje para 'same' si no existe
            $singleMessages += [
                'password_confirmation.same' => $messages['password_confirmation.same'] ?? 'La confirmación debe coincidir con la contraseña.',
            ];

            $validator = Validator::make($requestData, $singleRule, $singleMessages);

            if ($validator->fails()) {
                $key = array_key_first($singleRule); // 'password' o 'password_confirmation' o el campo que toque
                return response()->json(['error' => $validator->errors()->first($key)], 422);
            }

            // Validation passed
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Server error during validation.'], 500);
        }
    }
}
