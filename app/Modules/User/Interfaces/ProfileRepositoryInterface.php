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

    // =========================================================================
    // OTP
    // =========================================================================

    /**
     * Tìm OTP hợp lệ theo user_id.
     *
     * Ưu tiên dùng method này thay vì findValidOtpByPhone() vì tránh nhầm lẫn
     * khi phone của user vừa được thay đổi trong cùng flow.
     *
     * @param  int         $userId
     * @param  UserOtpType $type
     * @return UserOtp|null
     */
    public function findValidOtpByUserId(int $userId, UserOtpType $type): ?UserOtp;

    /**
     * Tìm OTP hợp lệ theo phone.
     *
     * Dùng cho các flow chưa authenticate (ví dụ: đăng ký, quên mật khẩu)
     * khi chưa có user_id.
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
     * @return UserOtp  Trả về instance đã được refresh sau khi increment.
     */
    public function incrementOtpAttempts(UserOtp $userOtp): UserOtp;

    /**
     * Đánh dấu OTP đã được xác thực và consume.
     *
     * Set cả verified_at và used_at trong cùng một lần ghi vì xác thực
     * và consume diễn ra trong cùng một transaction.
     *
     * @param  UserOtp $otp
     * @return void
     */
    public function markOtpAsUsed(UserOtp $otp): void;

    /**
     * Invalidate tất cả OTP còn hiệu lực của user (cùng type).
     *
     * Gọi trước khi tạo OTP mới để đảm bảo không có nhiều OTP hợp lệ
     * tồn tại song song.
     *
     * @param  int         $userId
     * @param  UserOtpType $type
     * @return void
     */
    public function invalidatePreviousOtps(int $userId, UserOtpType $type): void;
}
