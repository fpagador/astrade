<?php

namespace App\Repositories;

use App\Models\User;

/**
 * Repository class for handling database interactions related to the User entity.
 */
class UserRepository
{
    /**
     * Get the authenticated user with related entities loaded.
     *
     * @param int $userId
     * @return User|null
     */
    public function findWithProfileData(int $userId): ?User
    {
        return User::with([
            'role:id,role_name',
            'company.phones',
        ])->find($userId);
    }

    /**
     * Update the FCM token for the given user.
     *
     * @param User $user
     * @param string $fcmToken
     * @return User
     */
    public function updateFcmToken(User $user, string $fcmToken): User
    {
        $user->fcm_token = $fcmToken;
        $user->save();

        return $user;
    }

    /**
     * Delete the FCM token for the given user.
     *
     * @param User $user
     * @return User
     */
    public function deleteFcmToken(User $user): User
    {
        $user->fcm_token = null;
        $user->save();

        return $user;
    }
}
