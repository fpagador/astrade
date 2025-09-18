<?php

namespace App\Http\Controllers\Api;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserApiController extends ApiController
{
    protected UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * Profile of the authenticated user.
     *
     * @OA\Get(
     *     path="/api/profile",
     *     summary="Get authenticated user profile",
     *     tags={"User"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile retrieved",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     *
     */
    public function profile(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $user = $this->service->getProfile($request->user()->id);
            return $this->render($user);
        }, 'Error getting user profile', $request);
    }

    /**
     * Get all active users' phone numbers.
     *
     * @OA\Get(
     *     path="/api/phones",
     *     summary="Get phone numbers of users that can receive calls",
     *     tags={"User"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Active phone numbers retrieved",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="phones",
     *                 type="array",
     *                 @OA\Items(type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     */
    public function getPhone(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $parsedPhone = $this->service->getPhones();
            return $this->render($parsedPhone);
        }, 'Error retrieving active phone numbers', $request);
    }

    /**
     * Update FCM Token
     *
     * @OA\Post(
     *     path="/api/fcm/update",
     *     summary="Update the user's FCM token",
     *     tags={"User"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"fcm_token"},
     *             @OA\Property(property="fcm_token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FCM token updated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $request->validate([
                'fcm_token' => ['required', 'string'],
            ]);

            return $this->render(
                $this->service->updateFcmToken($request->user(), $request->input('fcm_token')),
                'FCM token updated successfully');
        }, 'Error updating FCM token', $request);
    }

    /**
     * Delete FCM Token
     *
     * @OA\Post(
     *     path="/api/fcm/delete",
     *     summary="Delete the user's FCM token",
     *     tags={"User"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="FCM token deleted",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function deleteFcmToken(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            return $this->render($this->service->deleteFcmToken($request->user()), 'FCM token deleted successfully');
        }, 'Error deleting FCM token', $request);
    }


}
