<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\ProfileRepositoryInterface;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\User;
use App\Modules\User\Model\UserOtp;
use Illuminate\Support\Arr;

class ProfileRepository extends BaseRepository implements ProfileRepositoryInterface
{
    // Các trường thuộc bảng users
    private const BASE_FIELDS = [
        'avatar', 'full_name', 'gender', 'address', 'email', 'citizen_id', 'phone', 'password'
    ];

    // Các trường thuộc bảng customer_profiles
    private const CUSTOMER_FIELDS = [
        'birthday'
    ];

    /**
     * {@inheritdoc}
     */
    public function getModel(): string
    {
        return User::class;
    }

    /**
     * {@inheritdoc}
     */
    public function updateProfile(User $user, array $data): User
    {
        // 1. Tách dữ liệu cho bảng users và bảng profile tương ứng
        $baseData = Arr::only($data, self::BASE_FIELDS);
        $profileData = Arr::except($data, self::BASE_FIELDS);

        // 2. Cập nhật bảng users nếu có dữ liệu
        if (!empty($baseData)) {
            $user->update($baseData);
        }

        // 3. Cập nhật bảng profile tương ứng với vai trò
        if (!empty($profileData)) {
            match ($user->role) {
                UserRole::Customer => $this->updateCustomerProfile($user, $profileData),
                // Thêm logic cho Driver, Merchant ở đây nếu cần
                // UserRole::Driver => $this->updateDriverProfile($user, $profileData),
                // UserRole::Merchants => $this->updateMerchantProfile($user, $profileData),
                default => null,
            };
        }

        return $user->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function findValidOtp(string $phone, UserOtpType $type): ?UserOtp
    {
        return UserOtp::where('phone', $phone)
            ->where('type', $type)
            ->whereNull('verified_at')
            ->where('expired_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function incrementOtpAttempts(UserOtp $otp): void
    {
        $otp->increment('attempts');
    }

    /**
     * {@inheritdoc}
     */
    public function markOtpAsVerified(UserOtp $otp): void
    {
        $otp->update([
            'verified_at' => now(),
        ]);
    }

    /**
     * Cập nhật hồ sơ khách hàng.
     */
    private function updateCustomerProfile(User $user, array $data): void
    {
        $customerData = Arr::only($data, self::CUSTOMER_FIELDS);
        if (!empty($customerData)) {
            $user->customerProfile()->updateOrCreate(['user_id' => $user->id], $customerData);
        }
    }
}
