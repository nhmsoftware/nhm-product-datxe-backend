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
    public function updateUser(User $user, array $data): User;

    /**
     * Cập nhật các bảng profile liên quan (customer_profile, driver_profile, merchant_profile).
     * @param string $phone
     * @param UserOtpType $type
     * @return UserOtp|null
     */
    public function findValidOtp(string $phone, UserOtpType $type): ?UserOtp;

    /**
     * Tăng số lần nhập sai OTP
     * @param UserOtp $userOtp
     * @return UserOtp/
     * @throws \Exception
     */
    public function incrementOtpAttempts(UserOtp $userOtp): UserOtp;

    /**
     * Đánh dấu OTP đã xác thực
     * @param UserOtp $otp
     */
    public function markOtpAsVerified(UserOtp $otp): void;
}
