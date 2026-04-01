<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\User;
use App\Modules\User\Model\UserDevice;
use App\Modules\User\Model\UserOtp;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function getModel()
    {
       return User::class;
    }

    public function findByPhone(string $phone): ?User
    {
        return $this->query()
            ->where('phone', $phone)
            ->where('is_verified', true)
            ->withTrashed()
            ->first();
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
