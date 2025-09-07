<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\NotificationType;
use App\Enums\RoleEnum;
use App\Enums\UserTypeEnum;
use App\Http\Controllers\Web\WebController;
use App\Http\Requests\Admin\StoreOrUpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
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
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ContractType;

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

            if ($type === UserTypeEnum::MOBILE->value) {
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

            $sort = $request->get('sort', 'name');
            $direction = $request->get('direction', 'asc');

            // default columns
            $sortableColumns = ['name', 'surname', 'dni', 'email', 'phone'];

            // external relations columns
            $sortableRelations = [
                'role'    => ['table' => 'roles', 'local_key' => 'role_id', 'foreign_key' => 'id', 'column' => 'role_name'],
                'company' => ['table' => 'companies', 'local_key' => 'company_id', 'foreign_key' => 'id', 'column' => 'name'],
            ];

            if (in_array($sort, $sortableColumns)) {
                $query->orderBy("users.$sort", $direction);
            } elseif (array_key_exists($sort, $sortableRelations)) {
                $relation = $sortableRelations[$sort];

                $query->leftJoin($relation['table'], "{$relation['table']}.{$relation['foreign_key']}", '=', "users.{$relation['local_key']}")
                    ->orderBy("{$relation['table']}.{$relation['column']}", $direction)
                    ->select('users.*');
            }

            $users = $query->paginate(15)->appends($request->query());

            //Dynamic view by type
            if ($type === UserTypeEnum::MOBILE->value) {
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
        $type = $request->get('type', UserTypeEnum::MOBILE->value);
        $backUrl = $request->get('back_url');
        return view('web.admin.users.show', compact('user', 'type', 'backUrl'));
    }

    /**
     * Prepare data for the create and edit user views
     *
     * @param Request $request
     * @param ?User $user
     * @return array
     */
    private function getFormData(Request $request, ?User $user = null): array
    {
        $authUser = auth()->user();

        // Assignable roles
        $assignableRoleNames = match (true) {
            $authUser->hasRole('admin') => ['admin','manager','user'],
            $authUser->hasRole('manager') => ['user'],
            default => [],
        };
        $assignableRoles = Role::whereIn('role_name', $assignableRoleNames)->get();

        // Default role
        $defaultRole = $request->role
            ? Role::where('role_name', $request->role)->first()?->id
            : ($user->role_id ?? null);

        // Companies
        $companies = Company::all();

        // Work Calendar
        $workCalendarTemplate = WorkCalendarTemplate::where('status', 'active')
            ->orderBy('year', 'desc')->get();

        // Contract type options
        $contractOptions = collect(ContractType::cases())
            ->mapWithKeys(fn($case) => [$case->value => ContractType::label($case)])
            ->prepend('-- Selecciona un tipo --', '');

        // Notification options
        $notificationTypeOptions = collect(NotificationType::cases())
            ->mapWithKeys(fn($case) => [$case->value => NotificationType::label($case)])
            ->prepend('-- Selecciona un tipo --', '');

        return compact(
            'assignableRoles',
            'defaultRole',
            'companies',
            'workCalendarTemplate',
            'contractOptions',
            'notificationTypeOptions'
        );
    }

    /**
     * Show the form for creating a new user.
     *
     * @param Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        $formData = $this->getFormData($request);

        return view('web.admin.users.create', $formData);
    }

    /**
     * Store a newly created user in storage.
     *
     * @param StoreOrUpdateUserRequest $request
     * @return View|RedirectResponse
     */
    public function store(StoreOrUpdateUserRequest $request): View|RedirectResponse
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

            $validated['can_receive_notifications'] = true;
            if ($validated['notification_type'] !== 'none') {
                $validated['can_receive_notifications'] = false;
            }

            $validated['role_id'] = $request->role_id;

            User::create($validated);
            $type = $role->role_name === RoleEnum::USER->value ? UserTypeEnum::MOBILE->value : UserTypeEnum::MANAGEMENT->value;

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
            $user = User::findOrFail($id);
            $formData = $this->getFormData($request, $user);
            $formData['user'] = $user;

            return view('web.admin.users.edit', $formData);
        }, route('admin.users.index'));
    }

    /**
     * Update the specified user in storage.
     *
     * @param StoreOrUpdateUserRequest $request
     * @param int $id
     * @return View|RedirectResponse
     */
    public function update(StoreOrUpdateUserRequest $request, int $id): View|RedirectResponse
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

            $validated['can_receive_notifications'] = true;
            if ($validated['notification_type'] !== 'none') {
                $validated['can_receive_notifications'] = false;
            }

            $user->update($validated);

            $role = Role::findOrFail($request->input('role_id'));
            $type = $role->role_name === RoleEnum::USER->value ? UserTypeEnum::MOBILE->value : UserTypeEnum::MANAGEMENT->value;

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
     * @param Request $user
     * @param User $user
     * @return View
     */
    public function editPassword(Request $request, User $user): View
    {
        $this->authorize('changePassword', $user);
        $backUrl = $request->get('back_url');
        return view('web.admin.users.edit-password', compact('user', 'backUrl'));
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
        return $this->tryCatch(function () use ($request, $user) {
            $this->authorize('changePassword', $user);
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return redirect()->route('admin.users.index', ['type' => UserTypeEnum::MOBILE->value]);
        }, route('admin.users.index'), 'Contraseña actualizada correctamente.');
    }

    /**
     * Validate a single field using the given FormRequest.
     *
     * @param Request $request
     * @param string $formRequestClass
     * @return JsonResponse
     */
    protected function validateSingleField(Request $request, string $formRequestClass): JsonResponse
    {
        try {
            $field = $request->input('field');
            $value = $request->input('value');

            /** @var FormRequest $formRequest */
            $formRequest = new $formRequestClass;

            // Initializes the FormRequest with all the necessary parameters
            $formRequest->initialize(
                $request->all(),
                $request->post(),
                $request->attributes->all(),
                $request->cookies->all(),
                $request->files->all(),
                $request->server->all(),
                $request->getContent()
            );

            $formRequest->merge([
                'type' => $request->input('type'),
                $field => $value
            ]);

            // For password and confirmation, we ensure that both fields are
            if (in_array($field, ['password', 'password_confirmation'])) {
                $formRequest->merge($request->only(['password', 'password_confirmation']));
            }

            $rules = $formRequest->rules();
            $messages = $formRequest->messages();

            //We select only the field rule
            $singleRule = match($field) {
                'password' => array_filter($rules['password'] ?? [], fn($r) => $r !== 'confirmed'),
                'password_confirmation' => ['same:password'],
                default => $rules[$field] ?? []
            };

            $singleRule = is_array($singleRule) ? [$field => $singleRule] : [$field => [$singleRule]];

            // We filter the messages only for that field
            $singleMessages = array_filter(
                $messages,
                fn($key) => str_starts_with($key, $field . '.'),
                ARRAY_FILTER_USE_KEY
            );

            //We ensure message for password_confirmation
            if ($field === 'password_confirmation') {
                $singleMessages['password_confirmation.same'] =
                    $singleMessages['password_confirmation.same'] ?? 'La confirmación debe coincidir con la contraseña.';
            }

            $validator = Validator::make($formRequest->all(), $singleRule, $singleMessages);

            if ($validator->fails()) {
                $key = array_key_first($singleRule);
                return response()->json(['error' => $validator->errors()->first($key)], 200);
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Server error during validation.'], 500);
        }
    }

    /**
     * Validate a single field from the request using StoreUserRequest rules.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateField(Request $request): JsonResponse
    {
        return $this->validateSingleField($request, StoreOrUpdateUserRequest::class);
    }

    /**
     * Validate passwords using UpdateUserPasswordRequest rules.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validatePassword(Request $request): JsonResponse
    {
        return $this->validateSingleField($request, UpdateUserPasswordRequest::class);
    }
}
