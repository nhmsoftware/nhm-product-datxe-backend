<?php

declare(strict_types=1);

namespace Modules\User\Domain\Interfaces;

use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Entities\UserOtp;
use Modules\User\Domain\Enums\UserOtpType;
use Modules\User\Domain\Enums\UserRole;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByPhone(string $phone): ?User;

    public function existsByPhone(string $phone): bool;

    public function create(array $data): User;

    public function createCustomerProfile(int $userId, array $data): void;

    public function upsertDevice(int $userId, array $data): void;

    // ─── OTP ─────────────────────────────────────────────────────
    public function findLatestOtp(string $phone, UserOtpType $type): ?UserOtp;

    public function upsertOtp(array $data): UserOtp;

    public function markOtpVerified(UserOtp $otp): void;

    public function incrementOtpAttempts(UserOtp $otp): void;
}
