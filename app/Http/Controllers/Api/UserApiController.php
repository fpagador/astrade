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
}
