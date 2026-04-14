<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\ProfileRepositoryInterface;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\User;
use App\Modules\User\Model\UserOtp;

class ProfileRepository extends BaseRepository implements ProfileRepositoryInterface
{
    public function getModel(): string
    {
        return User::class;
    }

    /**
     * Cập nhật bảng users.
     *
     * Mặc định phone và email bị chặn để tránh mass-assignment vô tình.
     * Chỉ set $allowSensitive = true khi đã qua bước xác thực OTP.
     */
    public function updateUser(User $user, array $data, bool $allowSensitive = false): User
    {
        if (!$allowSensitive) {
            unset($data['phone'], $data['email']);
        }

        $fillable = $user->getFillable();
        $user->update(array_intersect_key($data, array_flip($fillable)));

        return $user->refresh();
    }

    /**
     * Cập nhật các bảng profile liên quan (customer, driver, merchant).
     * Chỉ update profile nào tồn tại và có field phù hợp.
     */
    public function updateProfiles(User $user, array $data): void
    {
        $profiles = [
            $user->customerProfile,
            $user->driverProfile,
            $user->merchantProfile,
        ];

        foreach ($profiles as $profile) {
            if (!$profile) {
                continue;
            }

            $profileData = array_intersect_key($data, array_flip($profile->getFillable()));

            if (!empty($profileData)) {
                $profile->update($profileData);
            }
        }
    }

    /**
     * Tìm OTP hợp lệ theo phone và type.
     * Chỉ trả về OTP chưa dùng (used_at IS NULL) và chưa hết hạn.
     */
    public function findValidOtpByPhone(string $phone, UserOtpType $type): ?UserOtp
    {
        return UserOtp::where('phone', $phone)
            ->where('type', $type->value)
            ->whereNull('used_at')
            ->where('expired_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Tăng số lần nhập OTP sai.
     */
    public function incrementOtpAttempts(UserOtp $userOtp): UserOtp
    {
        $userOtp->increment('attempts');

        return $userOtp->fresh();
    }

    /**
     * Đánh dấu OTP đã được xác thực và consume.
     */
    public function markOtpAsUsed(UserOtp $otp): void
    {
        $now = now();

        $otp->update([
            'verified_at' => $now,
            'used_at'     => $now,
        ]);
    }

    /**
     * Invalidate tất cả OTP còn hiệu lực (cùng phone + type).
     * Gọi trước khi tạo OTP mới để tránh nhiều OTP hợp lệ tồn tại song song.
     */
    public function invalidatePreviousOtps(string $phone, UserOtpType $type): void
    {
        UserOtp::where('phone', $phone)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expired_at', '>', now())
            ->update(['used_at' => now()]);
    }
}
