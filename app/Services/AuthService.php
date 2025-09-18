<?php

namespace App\Services;

use App\Repositories\AuthRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use App\Models\Log;
use App\Models\User;

/**
 * Service class responsible for handling authentication logic,
 * abstracting repository calls, and business rules.
 */
class AuthService
{

    /**
     * AuthService constructor.
     *
     * @param AuthRepository $authRepository
     */
    public function __construct(
        AuthRepository $authRepository
    ) {}

    /**
     * Perform login and return access token.
     *
     * @param array $credentials
     * @param Request $request
     * @return array
     * @throws AuthenticationException
     */
    public function login(array $credentials, Request $request): array
    {
        if (!$this->authRepository->attemptLogin($credentials)) {
            throw new AuthenticationException('Invalid credentials');
        }

        $user = $request->user();
        $token = $this->authRepository->createToken($user);

        // Record login log
        Log::record('info', 'Successful login', [
            'user_id' => $user->id,
            'dni'     => $request->input('dni')
        ]);

        return [
            'access_token' => $token,
            'token_type'   => 'Bearer'
        ];
    }

    /**
     * Refresh user token: delete current token and issue a new one.
     *
     * @param User $user
     * @return array
     * @throws AuthenticationException
     */
    public function refresh(User $user): array
    {
        if (!$user) {
            throw new AuthenticationException('Unauthorized');
        }

        $this->authRepository->deleteCurrentToken($user);
        $newToken = $this->authRepository->createToken($user, 'mobile');

        return [
            'token' => $newToken,
        ];
    }

    /**
     * Logout user by revoking all tokens.
     *
     * @param User $user
     * @return void
     */
    public function logout(User $user): void
    {
        $this->authRepository->deleteAllTokens($user);
    }
}
