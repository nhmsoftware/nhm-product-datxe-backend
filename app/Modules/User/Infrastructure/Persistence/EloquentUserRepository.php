<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence;

use Modules\User\Domain\Entities\CustomerProfile;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Entities\UserDevice;
use Modules\User\Domain\Entities\UserOtp;
use Modules\User\Domain\Enums\UserOtpType;
use Modules\User\Domain\Interfaces\UserRepositoryInterface;

final class EloquentUserRepository implements UserRepositoryInterface
{
    // ─── User ─────────────────────────────────────────────────────

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    public function existsByPhone(string $phone): bool
    {
        return User::where('phone', $phone)->exists();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    // ─── CustomerProfile ──────────────────────────────────────────

    public function createCustomerProfile(int $userId, array $data): void
    {
        CustomerProfile::create(array_merge($data, ['user_id' => $userId]));
    }

    // ─── Device ───────────────────────────────────────────────────

    public function upsertDevice(int $userId, array $data): void
    {
        UserDevice::updateOrCreate(
            [
                'user_id'   => $userId,
                'device_id' => $data['device_id'],
            ],
            [
                'token'       => $data['token']       ?? null,
                'device_type' => $data['device_type'] ?? null,
            ]
        );
    }

    // ─── OTP ──────────────────────────────────────────────────────

    public function findLatestOtp(string $phone, UserOtpType $type): ?UserOtp
    {
        return UserOtp::where('phone', $phone)
            ->where('type', $type->value)
            ->latest('created_at')
            ->first();
    }

    public function upsertOtp(array $data): UserOtp
    {
        // Mỗi phone+type chỉ giữ 1 record (upsert theo phone+type)
        return UserOtp::updateOrCreate(
            [
                'phone' => $data['phone'],
                'type'  => $data['type'],
            ],
            $data
        );
    }

    public function markOtpVerified(UserOtp $otp): void
    {
        $otp->update(['verified_at' => now()]);
    }

    public function incrementOtpAttempts(UserOtp $otp): void
    {
        $otp->increment('attempts');
    }
}
