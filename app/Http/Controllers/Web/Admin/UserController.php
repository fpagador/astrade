<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\UserTypeEnum;
use App\Http\Controllers\Web\WebController;
use App\Http\Requests\Admin\StoreOrUpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Models\User;
use App\Repositories\RoleRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Models\Company;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Repositories\UserRepository;
use App\Services\UserService;

class UserController extends WebController
{

    /**
     * Construct
     *
     * @param UserRepository $userRepository
     * @param UserService $userService
     * @param RoleRepository $roleRepository
     */
    public function __construct(
        protected UserRepository $userRepository,
        protected UserService $userService,
        protected RoleRepository $roleRepository
    ) {}

    /**
     * Display a paginated list of manager users with optional filters.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function index(Request $request): View|RedirectResponse
    {
        return $this->tryCatch(function () use ($request) {
            $type = $request->get('type', UserTypeEnum::MANAGEMENT->value);

            // Extract only the filters we expect
            $filters = $request->only(['name','email','dni','role','company_id','sort','direction']);
            $users = $this->userService->paginateUsersWithWarnings($type, $filters);

            if ($type === UserTypeEnum::MOBILE->value) {
                $companies = Company::orderBy('name')->get();
                return view('web.admin.users.mobile', compact('users', 'companies'));
            }

            return view('web.admin.users.index', compact('users'));
        });
    }

    /**
     * Show the details of a single user.
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
     * Show the form for creating a new user.
     *
     * @param Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        $formData = $this->userService->getFormData($request);

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

            // Pass file separately
            if ($request->hasFile('photo')) {
                $validated['photo_file'] = $request->file('photo');
            }

            $role = $this->roleRepository->findById($request->role_id);
            $allowedRoles = $this->userService->getAllowedRoleNames(auth()->user());
            if (!in_array($role->role_name, $allowedRoles)) {
                return redirect()->route('admin.users.create')->with('error', 'You cannot assign this role.');
            }

            $validated['role_id'] = $role->id;
            $this->userService->createUser($validated);

            $type = $this->userService->setUserTypeFromRole($role->role_name);

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
            $user = $this->userRepository->find($id);
            $formData = $this->userService->getFormData($request, $user);
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
            $user = $this->userRepository->find($id);
            $validated = $request->validated();

            if ($request->hasFile('photo')) {
                $validated['photo_file'] = $request->file('photo');
            }

            $this->userService->updateUser($user, $validated);

            $type = $this->userService->setUserTypeFromRole($user->role->role_name ?? '');

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
            $user = $this->userRepository->find($id);
            if ($user->photo) Storage::disk('public')->delete($user->photo);
            $this->userRepository->delete($user);

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
            $user->update(['password' => Hash::make($request->password)]);

            return redirect()->route('admin.users.index', ['type' => UserTypeEnum::MOBILE->value]);
        }, route('admin.users.index'), 'Contraseña actualizada correctamente.');
    }

    /**
     * Validate a single field from the request using StoreUserRequest rules.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateField(Request $request): JsonResponse
    {
        $result = $this->userService->validateSingleField($request, StoreOrUpdateUserRequest::class);
        return response()->json($result, isset($result['error']) ? 422 : 200);
    }

    /**
     * Validate passwords using UpdateUserPasswordRequest rules.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validatePassword(Request $request): JsonResponse
    {
        $result = $this->userService->validateSingleField($request, UpdateUserPasswordRequest::class);
        return response()->json($result, isset($result['error']) ? 422 : 200);
    }

    /**
     * Export users to Excel with all filters applied.
     *
     *
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request):BinaryFileResponse
    {
        $type = $request->get('type', UserTypeEnum::MANAGEMENT->value);
        $filters = $request->only(['name','email','dni','role','company_id','sort','direction']);

        $query = $this->userRepository->queryUsers($type, $filters);

        $fileName = $type === UserTypeEnum::MOBILE->value
            ? 'usuarios_móviles_' . now()->format('Ymd_His') . '.xlsx'
            : 'usuarios_gestión_interna_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new UsersExport($query, $type), $fileName);
    }

}
