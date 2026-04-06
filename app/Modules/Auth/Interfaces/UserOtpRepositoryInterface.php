<?php

namespace App\Modules\Auth\Interfaces;

use App\Modules\Auth\Model\Enums\UserOtpType;
use App\Modules\Auth\Model\UserOtp;

interface UserOtpRepositoryInterface
{


    /**
     * Lấy OTP cuối cùng
     * @param string $phone
     * @param UserOtpType $type
     * @return UserOtp|null
     */
    public function getLastOtp(string $phone, UserOtpType $type): ?UserOtp;

    /**
     * Tạo OTP
     * @param string $phone
     * @param UserOtpType $type
     * @return UserOtp
     */
    public function generateOtp(string $phone, UserOtpType $type): UserOtp;

    /**
     * Lấy OTP cuối cùng đã được xác thực
     * @param string $phone
     * @param UserOtpType $type
     * @return UserOtp|null
     */
    public function getLastVerified(string $phone, UserOtpType $type): ?UserOtp;

    /**
     * Lấy số lần gửi OTP trong ngày
     * @param string $phone
     * @param UserOtpType $type
     * @return int
     */
    public function countSentToday(string $phone, UserOtpType $type): int;

    /**
     * Tăng số lần thử
     * @param UserOtp $otp
     * @return void
     */
    public function incrementAttempts(UserOtp $otp): void;

    /**
     * Đánh dấu OTP là đã sử dụng
     * @param string $phone
     * @param UserOtpType $type
     * @return void
     */
    public function markLatestAsUsed(string $phone, UserOtpType $type): void;

    /**
     * Đánh dấu OTP là đã xác thực
     * @param UserOtp $otp
     * @return void
     */
    public function markAsVerified(UserOtp $otp): void;
}
