<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Log;
use App\Http\Controllers\Api\Traits\HandlesApiErrors;
use Illuminate\Support\Facades\Auth;

class AuthController extends ApiController
{
    use HandlesApiErrors;
    /**
     * Perform user authentication using ID and password
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        return $this->handleApi(
            function () use ($request) {
                $credentials = $request->validated();

                if (!auth()->attempt($credentials)) {
                    return $this->render(null, 'Invalid credentials', 401);
                }

                $token = $request->user()->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'access_token' => $token,
                    'token_type' => 'Bearer'
                ]);
            }, 'Login error', $request,
            function () use ($request) {
                Log::record('info', 'Successful login', [
                    'user_id' => $request->user()->id,
                    'dni' => $request->input('dni')
                ]);
            }
        );
    }

    /**
     * Refresh the authentication token for the authenticated user.
     *
     * This endpoint allows mobile clients to request a new access token
     * using a valid current token. It deletes the old token and issues a new one.
     *
     * @param  Request  $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/refresh",
     *     summary="Refresh user token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="token", type="string", example="1|abcd1234...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="New access token issued",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token refreshed successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string", example="2|xyz987...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid or expired token"
     *     )
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $user = Auth::user();

            if (!$user) {
                return $this->render(null, 'Unauthorized', 401);
            }

            // Delete current token
            $request->user()->currentAccessToken()->delete();

            // Create new token
            $newToken = $user->createToken('mobile')->plainTextToken;

            // Expiration time from Sanctum config (in minutes)
            $expiresInMinutes = config('sanctum.expiration');
            $expiresIn = $expiresInMinutes ? $expiresInMinutes * 60 : null;

            return $this->render([
                'token' => $newToken,
                'expires_in' => $expiresIn, // seconds (null = no expiration)
            ], 'Token refreshed successfully');
        }, 'Error refreshing token', $request);
    }

    /**
     * Function to logout a user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request)
    {
        return $this->handleApi(function () use ($request) {
            $request->user()->tokens()->delete();
            return $this->render(null, 'Successfully logged out');
        }, 'Logout error', $request);
    }
}
