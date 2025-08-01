<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Controllers\Api\Traits\HandlesApiErrors;

class UserApiController extends ApiController
{
    use HandlesApiErrors;

    /**
     * Profile of the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            return $this->render($request->user());
        }, 'Error getting user profile', $request);
    }

    /**
     * Update the authenticated user profile.
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            /** @var User $user */
            $user = $request->user();
            $user->update($request->validated());

            return $this->render($user, 'Profile updated successfully');
        }, 'Error updating user profile', $request);
    }

    /**
     * View another user's profile by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    /*
    public function show(int $id): JsonResponse
    {
        return $this->handleApi(function () use ($id) {
            $user = User::with('roles', 'notificationPreference')->findOrFail($id);
            return $this->render($user);
        }, 'Error al obtener el perfil del usuario');
    }
    */

    /**
     * Update FCM Token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $request->validate([
                'fcm_token' => ['required', 'string'],
            ]);

            /** @var User $user */
            $user = $request->user();
            $user->fcm_token = $request->input('fcm_token');
            $user->save();

            return $this->render($user, 'FCM token updated successfully');
        }, 'Error updating FCM token', $request);
    }

    /**
     * Delete FCM Token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteFcmToken(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            /** @var User $user */
            $user = $request->user();
            $user->fcm_token = null;
            $user->save();

            return $this->render($user, 'FCM token deleted successfully');
        }, 'Error deleting FCM token', $request);
    }


}
