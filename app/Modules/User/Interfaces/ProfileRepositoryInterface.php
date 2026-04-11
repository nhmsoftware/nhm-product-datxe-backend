<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\User;
use App\Modules\User\Model\UserOtp;

interface ProfileRepositoryInterface extends BaseRepositoryInterface
{
    // =========================================================================
    // User
    // =========================================================================

    /**
     * Cập nhật bảng users.
     *
     * Mặc định phone và email bị chặn để tránh mass-assignment vô tình.
     * Chỉ set $allowSensitive = true khi đã qua bước xác thực OTP.
     *
     * @param  User  $user
     * @param  array $data
     * @param  bool  $allowSensitive
     * @return User
     */
    public function updateUser(User $user, array $data, bool $allowSensitive = false): User;

    /**
     * Cập nhật các bảng profile liên quan (customer_profile, driver_profile, merchant_profile).
     * Chỉ update profile nào tồn tại và có field phù hợp với $data.
     *
     * @param  User  $user
     * @param  array $data
     * @return void
     */
    public function updateProfiles(User $user, array $data): void;

    /**
     * Tìm OTP hợp lệ theo phone và type.
     * Chỉ trả về OTP chưa dùng (used_at IS NULL) và chưa hết hạn.
     *
     * @param  string      $phone
     * @param  UserOtpType $type
     * @return UserOtp|null
     */
    public function findValidOtpByPhone(string $phone, UserOtpType $type): ?UserOtp;

    /**
     * Tăng số lần nhập OTP sai.
     *
     * @param  UserOtp $userOtp
     * @return UserOtp  Trả về instance đã refresh sau khi increment.
     */
    public function incrementOtpAttempts(UserOtp $userOtp): UserOtp;

    /**
     * Đánh dấu OTP đã được xác thực và consume.
     *
     * @param  UserOtp $otp
     * @return void
     */
    public function markOtpAsUsed(UserOtp $otp): void;

    /**
     * Invalidate tất cả OTP còn hiệu lực (cùng phone + type).
     * Gọi trước khi tạo OTP mới để tránh nhiều OTP hợp lệ tồn tại song song.
     *
     * @param  string      $phone
     * @param  UserOtpType $type
     * @return void
     */
    public function invalidatePreviousOtps(string $phone, UserOtpType $type): void;
}
