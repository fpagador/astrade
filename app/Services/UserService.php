<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

/**
 * Service class for business logic related to the User entity.
 */
class UserService
{
    protected UserRepository $repository;

    /**
     * UserService constructor.
     *
     * @param UserRepository $repository
     */
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Retrieve the authenticated user's profile with related entities.
     *
     * @param int $userId
     * @return User|null
     */
    public function getProfile(int $userId): ?User
    {
        return $this->repository->findWithProfileData($userId);
    }

    /**
     * Extract and sanitize the phone number for the given user.
     *
     * @param User $user
     * @return string|null
     */
    public function getPhone(User $user): ?string
    {
        $rawPhone = '617972442';

        return $rawPhone ? preg_replace('/[^\d+]/', '', $rawPhone) : null;
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
        return $this->repository->updateFcmToken($user, $fcmToken);
    }

    /**
     * Delete the FCM token for the authenticated user.
     *
     * @param User $user
     * @return User
     */
    public function deleteFcmToken(User $user): User
    {
        return $this->repository->deleteFcmToken($user);
    }
}
