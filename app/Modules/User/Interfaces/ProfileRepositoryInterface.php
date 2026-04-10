<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Core\Interfaces\BaseRepositoryInterface;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\User;
use App\Modules\User\Model\UserOtp;

interface ProfileRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Cập nhật dữ liệu người dùng và hồ sơ liên quan (customer, driver, etc.).
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function updateProfile(User $user, array $data): User;

    /**
     * Tìm mã OTP hợp lệ.
     *
     * @param string $phone
     * @param UserOtpType $type
     * @return UserOtp|null
     */
    public function findValidOtp(string $phone, UserOtpType $type): ?UserOtp;

    /**
     * Tăng số lần thử sai của OTP.
     *
     * @param UserOtp $otp
     * @return void
     */
    public function incrementOtpAttempts(UserOtp $userOtp): UserOtp;

    /**
     * Đánh dấu OTP đã được xác thực.
     *
     * @param UserOtp $otp
     * @return void
     */
    public function markOtpAsVerified(UserOtp $otp): void;
}
