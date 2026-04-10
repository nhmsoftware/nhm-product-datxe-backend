<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\ProfileRepositoryInterface;
use App\Modules\User\Model\User;
use App\Modules\User\Model\UserOtp;
use App\Modules\User\Model\Enums\UserOtpType;

class ProfileRepository extends BaseRepository implements ProfileRepositoryInterface
{
    public function getModel(): string
    {
        return User::class;
    }

    /**
     * Update bảng users
     */
    public function updateUser(User $user, array $data): User
    {
        $user->update($data);
        return $user->refresh();
    }

    /**
     * Update các bảng profile liên quan
     */
    public function updateProfiles(User $user, array $data): void
    {
        if ($user->customerProfile) {
            $user->customerProfile->update($data);
        }

        if ($user->driverProfile) {
            $user->driverProfile->update($data);
        }

        if ($user->merchantProfile) {
            $user->merchantProfile->update($data);
        }
    }

    /**
     * Tìm OTP hợp lệ
     */
    public function findValidOtp(string $phone, UserOtpType $type): ?UserOtp
    {
        return UserOtp::where('phone', $phone)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->where('expired_at', '>', now())
            ->latest()
            ->first();
    }


    /**
     * Tăng số lần thử OTP
     */
    public function incrementOtpAttempts(UserOtp $userOtp): UserOtp
    {
        $userOtp->increment('attempts');
        return $userOtp->fresh();
    }

    /**
     * Đánh OTP thành công
     */
    public function markOtpAsVerified(UserOtp $otp): void
    {
        $otp->update([
            'verified_at' => now(),
        ]);
    }
}
