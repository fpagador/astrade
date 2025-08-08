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
            $user = $request->user()
                ->load([
                    'role:id,role_name',
                    'company.phones'
                ]);
            return $this->render($user);
        }, 'Error getting user profile', $request);
    }

    /**
     * Profile of the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPhone(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $rawPhone = '617972442';
            $parsedPhone = preg_replace('/[^\d+]/', '', $rawPhone);
            
            return $this->render($parsedPhone);
        }, 'Error getting user profile', $request);
    }

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
