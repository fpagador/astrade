<?php

namespace App\Services;

use App\Enums\ContractType;
use App\Enums\NotificationType;
use App\Enums\RoleEnum;
use App\Enums\UserTypeEnum;
use App\Models\Company;
use App\Models\User;
use App\Models\WorkCalendarTemplate;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use App\Models\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Service class for business logic related to the User entity.
 */
class UserService
{

    /**
     * UserService constructor.
     *
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     */
    public function __construct(
        protected UserRepository $userRepository,
        protected RoleRepository $roleRepository
    ) {}

    //================================ API ======================================

    /**
     * Retrieve the authenticated user's profile with related entities.
     *
     * @param int $userId
     * @return User|null
     */
    public function getProfile(int $userId): ?User
    {
        return $this->userRepository->findWithProfileData($userId);
    }

    /**
     * Update the FCM token for the authenticated user.
     *
     * @param User $user
     * @param string $fcmToken
     * @return User
     */
    public function updateFcmToken(User $user, string $fcmToken): User
    {
        return $this->userRepository->updateFcmToken($user, $fcmToken);
    }

    /**
     * Delete the FCM token for the authenticated user.
     *
     * @param User $user
     * @return User
     */
    public function deleteFcmToken(User $user): User
    {
        return $this->userRepository->deleteFcmToken($user);
    }

    //================================ WEB ======================================

    /**
     * Create a new user with business logic applied.
     *
     * @param array $data
     * @return User
     */
    public function createUser(array $data): User
    {
        $data = $this->prepareUserData($data);
        return $this->userRepository->create($data);
    }

    /**
     * Update a user with business logic applied.
     *
     * @param array $data
     * @return User
     */
    public function updateUser(User $user, array $data): User
    {
        $data = $this->prepareUserData($data, $user);
        return $this->userRepository->update($user, $data);
    }

    /**
     * Centralized logic for handling passwords, notifications, and photos
     *
     * @param array $data
     * @param User $user
     * @return array
     */
    protected function prepareUserData(array $data, User $user = null): array
    {
        // Handle photo from file upload
        if (!empty($data['photo_file'])) {
            if ($user && $user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $data['photo'] = $data['photo_file']->store('photos', 'public');
        }
        // Handle photo from base64
        if (!empty($data['photo_base64'])) {
            $data['photo'] = $this->processPhoto64Bytes($data['photo_base64']);
        }

        // Hash password if present
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } elseif (isset($user)) {
            // Keep existing password on update if empty
            unset($data['password']);
        }

        // Notification flags
        $data['notification_type'] = $data['notification_type'] ?? 'none';
        $data['can_receive_notifications'] = $data['notification_type'] === 'none';

        return $data;
    }

    /**
     * Create a new user with business logic applied.
     *
     * @param array $data
     * @return string
     */
    public function processPhoto64Bytes($dataUri): string
    {
        // Detect extension
        if (preg_match('/^data:image\/(\w+);base64,/', $dataUri, $type)) {
            $dataUri = substr($dataUri, strpos($dataUri, ',') + 1);
            $extension = strtolower($type[1]); // jpg, png, etc.
        } else {
            $extension = 'png';
        }

        $tempFile = tmpfile();
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        file_put_contents($tempPath, base64_decode($dataUri));

        // Create a fake UploadedFile
        $uploadedFile = new UploadedFile(
            $tempPath,
            uniqid() . '.' . $extension,
            'image/' . $extension,
            null,
            true
        );

        // Use store() as usual
        return $uploadedFile->store('photos', 'public');
    }

    /**
     * Determine user type based on role name.
     *
     * @param string $roleName
     * @return string
     */
    public function setUserTypeFromRole(string $roleName): string
    {
        return $roleName === RoleEnum::USER->value
            ? UserTypeEnum::MOBILE->value
            : UserTypeEnum::MANAGEMENT->value;
    }

    /**
     * Get allowed roles for authenticated user.
     *
     * @param Authenticatable $authUser
     * @return array
     */
    public function getAllowedRoleNames( Authenticatable $authUser): array
    {
        return match (true) {
            $authUser->hasRole(RoleEnum::ADMIN->value) => [RoleEnum::ADMIN->value, RoleEnum::MANAGER->value, RoleEnum::USER->value],
            $authUser->hasRole(RoleEnum::MANAGER->value) => [RoleEnum::USER->value],
            default => [],
        };
    }

    /**
     * Prepare data for the create and edit user views
     *
     * @param Request $request
     * @param ?User $user
     * @return array
     */
    public function getFormData(Request $request, ?User $user = null): array
    {
        $type = $request->get('type', UserTypeEnum::MOBILE->value);

        $roleMap = [
            UserTypeEnum::MOBILE->value     => [RoleEnum::USER->value],
            UserTypeEnum::MANAGEMENT->value => [RoleEnum::ADMIN->value, RoleEnum::MANAGER->value],
        ];

        // Assignable roles
        $assignableRoleNames = $roleMap[$type] ?? [RoleEnum::USER->value];
        $assignableRoles = $this->roleRepository->findByNames($assignableRoleNames);

        // Default role
        $defaultRole = $request->role
            ? $this->roleRepository->getIdByName($request->role)
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

        $type = $request->get('type');

        return compact(
            'assignableRoles',
            'defaultRole',
            'companies',
            'workCalendarTemplate',
            'contractOptions',
            'notificationTypeOptions',
            'type'
        );
    }

    /**
     * Validate a single field using the given FormRequest.
     *
     * @param Request $request
     * @param string $formRequestClass
     * @return array
     */
    public function validateSingleField(Request $request, string $formRequestClass): array
    {
        try {
            $field = $request->input('field');
            $value = $request->input('value');

            /** @var FormRequest $formRequest */
            $formRequest = new $formRequestClass;
            $formRequest->initialize(
                $request->all(),
                $request->post(),
                $request->attributes->all(),
                $request->cookies->all(),
                $request->files->all(),
                $request->server->all(),
                $request->getContent()
            );
            $formRequest->merge([$field => $value, 'type' => $request->input('type')]);

            if (in_array($field, ['password', 'password_confirmation'])) {
                $formRequest->merge($request->only(['password', 'password_confirmation']));
            }

            $rules = $formRequest->rules();
            $messages = $formRequest->messages();

            $singleRule = match($field) {
                'password' => array_filter($rules['password'] ?? [], fn($r) => $r !== 'confirmed'),
                'password_confirmation' => ['same:password'],
                default => $rules[$field] ?? []
            };
            $singleRule = is_array($singleRule) ? [$field => $singleRule] : [$field => [$singleRule]];

            $singleMessages = array_filter(
                $messages,
                fn($key) => str_starts_with($key, $field . '.'),
                ARRAY_FILTER_USE_KEY
            );

            if ($field === 'password_confirmation') {
                $singleMessages['password_confirmation.same'] ??= 'La confirmación debe coincidir con la contraseña.';
            }

            $validator = Validator::make($formRequest->all(), $singleRule, $singleMessages);

            if ($validator->fails()) {
                $key = array_key_first($singleRule);
                return ['error' => $validator->errors()->first($key)];
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['error' => 'Server error during validation.'];
        }
    }

    /**
     * Paginate users and add warning flags for missing company or work calendar.
     *
     * @param array $filters
     * @param string $type
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginateUsersWithWarnings(string $type, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $users = $this->userRepository->paginateUsers($type, $filters, $perPage);

        $users->getCollection()->transform(function ($user) {
            $warnings = [];
            if (!$user->company_id) {
                $warnings[] = 'No hay empresa asignada';
            }
            if (!$user->work_calendar_template_id) {
                $warnings[] = 'No hay calendario laboral asignado';
            }

            $user->has_warning = !empty($warnings);
            $user->warning_title = $warnings ? implode(' y ', $warnings) : null;

            return $user;
        });

        return $users;
    }

}
