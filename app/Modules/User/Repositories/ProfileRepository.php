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

    // =========================================================================
    // User
    // =========================================================================

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

    // =========================================================================
    // OTP
    // =========================================================================

    /**
     * Tìm OTP hợp lệ theo user_id (tránh nhầm lẫn khi phone vừa thay đổi).
     * Chỉ lấy OTP chưa dùng và chưa hết hạn, ưu tiên bản mới nhất.
     */
    public function findValidOtpByUserId(int $userId, UserOtpType $type): ?UserOtp
    {
        return UserOtp::where('user_id', $userId)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expired_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Tìm OTP hợp lệ theo phone (dùng khi chưa có user_id trong flow).
     */
    public function findValidOtpByPhone(string $phone, UserOtpType $type): ?UserOtp
    {
        return UserOtp::where('phone', $phone)
            ->where('type', $type)
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
     *
     * - verified_at: thời điểm OTP được nhập đúng
     * - used_at:     thời điểm OTP được consume (dùng để block tái sử dụng)
     * Hai thời điểm này ghi cùng lúc vì flow xác thực và consume diễn ra trong
     * cùng một transaction.
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
     * Invalidate tất cả OTP cũ còn hiệu lực của user (cùng type).
     * Gọi trước khi tạo OTP mới để tránh tồn tại nhiều OTP song song.
     */
    public function invalidatePreviousOtps(int $userId, UserOtpType $type): void
    {
        UserOtp::where('user_id', $userId)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expired_at', '>', now())
            ->update(['used_at' => now()]);
    }
}
