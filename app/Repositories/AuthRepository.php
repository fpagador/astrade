<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Repository class responsible for handling authentication-related
 * database operations and token management.
 */
class AuthRepository
{
    /**
     * Attempt to authenticate a user with given credentials.
     *
     * @param array $credentials
     * @return bool
     */
    public function attemptLogin(array $credentials): bool
    {
        return Auth::attempt($credentials);
    }

    /**
     * Generate a new authentication token for the given user.
     *
     * @param User $user
     * @param string $tokenName
     * @return string
     */
    public function createToken(User $user, string $tokenName = 'auth_token'): string
    {
        return $user->createToken($tokenName)->plainTextToken;
    }

    /**
     * Delete the currently active access token for the authenticated user.
     *
     * @param User $user
     * @return void
     */
    public function deleteCurrentToken(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Delete all tokens for the authenticated user.
     *
     * @param User $user
     * @return void
     */
    public function deleteAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}
