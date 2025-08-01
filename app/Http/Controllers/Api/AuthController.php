<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Log;
use App\Http\Controllers\Api\Traits\HandlesApiErrors;

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
