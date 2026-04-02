<?php

namespace App\Modules\User\Interfaces;

use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\UserOtp;

interface UserOtpRepositoryInterface
{
    public function getLastOtp(string $phone, UserOtpType $type): ?UserOtp;
    public function generateOtp(string $phone, UserOtpType $type): UserOtp;
    public function getLastVerified(string $phone, UserOtpType $type): ?UserOtp;
    public function countSentToday(string $phone, UserOtpType $type): int;
    public function incrementAttempts(UserOtp $otp): void;
    public function markAsVerified(UserOtp $otp): void;
    public function markLatestAsUsed(string $phone, UserOtpType $type): void;
}
