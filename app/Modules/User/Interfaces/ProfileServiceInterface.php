<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Services\ServiceReturn;
use App\Modules\User\DTO\UpdateProfileDTO;
use App\Modules\User\Model\User;

interface ProfileServiceInterface
{
    /**
     * Get user profile with role-specific details.
     *
     * @param User $user
     * @return ServiceReturn
     */
    public function getProfile(User $user): ServiceReturn;

    /**
     * Update user profile with role-specific fields.
     */
    public function updateProfile(UpdateProfileDTO $dto): ServiceReturn;

    /**
     * Verify OTP and update sensitive fields.
     *
     * @param User $user
     * @param string $otp
     * @param array $sensitiveData
     * @return ServiceReturn
     */
    public function verifyAndUpdateSensitiveFields(User $user, string $otp, array $sensitiveData): ServiceReturn;

    /**
     * Change user password.
     *
     * @param User $user
     * @param string $newPassword
     * @return ServiceReturn
     */
    public function changePassword(User $user, string $newPassword): ServiceReturn;
}
