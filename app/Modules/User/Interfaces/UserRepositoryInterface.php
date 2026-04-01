<?php

declare(strict_types=1);

namespace App\Modules\User\Interfaces;

use App\Modules\User\Model\User;
use App\Modules\User\Model\UserOtp;
use App\Modules\User\Model\Enums\UserOtpType;

interface UserRepositoryInterface
{
    public function findByPhone(string $phone): ?User;

    public function existsByPhone(string $phone): bool;


    public function createCustomerProfile(int $userId, array $data): void;

    public function upsertDevice(int $userId, array $data): void;

    // ─── OTP ─────────────────────────────────────────────────────
    public function findLatestOtp(string $phone, UserOtpType $type): ?UserOtp;

    public function upsertOtp(array $data): UserOtp;

    public function markOtpVerified(UserOtp $otp): void;

    public function incrementOtpAttempts(UserOtp $otp): void;
}
