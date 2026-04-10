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
        $fillable = $user->getFillable();
        $user->update(array_intersect_key($data, array_flip($fillable)));
        return $user->refresh();
    }

    /**
     * Update các bảng profile liên quan (customer_profile, driver_profile, merchant_profile).
     */
    public function updateProfiles(User $user, array $data): void
    {
        if ($user->customerProfile) {
            $fillable = $user->customerProfile->getFillable();
            $user->customerProfile->update(array_intersect_key($data, array_flip($fillable)));
        }

        if ($user->driverProfile) {
            $fillable = $user->driverProfile->getFillable();
            $user->driverProfile->update(array_intersect_key($data, array_flip($fillable)));
        }

        if ($user->merchantProfile) {
            $fillable = $user->merchantProfile->getFillable();
            $user->merchantProfile->update(array_intersect_key($data, array_flip($fillable)));
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
