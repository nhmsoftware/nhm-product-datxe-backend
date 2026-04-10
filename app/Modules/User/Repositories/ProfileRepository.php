<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\ProfileRepositoryInterface;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\DriverProfile;
use App\Modules\User\Model\MerchantProfile;
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
        // 1. Cập nhật bảng users (chỉ các trường account/system)
        $userFillable = (new User())->getFillable();
        $userData = array_intersect_key($data, array_flip($userFillable));

        if (!empty($userData)) {
            $user->update($userData);
        }

        // 2. Cập nhật profile tương ứng dựa trên role của user
        // Chúng ta cập nhật tất cả profile hiện có của user này để đảm bảo đồng bộ
        if ($user->customerProfile) {
            $customerFillable = (new CustomerProfile())->getFillable();
            $customerData = array_intersect_key($data, array_flip($customerFillable));
            if (!empty($customerData)) {
                $user->customerProfile->update($customerData);
            }
        }

        if ($user->driverProfile) {
            $driverFillable = (new DriverProfile())->getFillable();
            $driverData = array_intersect_key($data, array_flip($driverFillable));
            if (!empty($driverData)) {
                $user->driverProfile->update($driverData);
            }
        }

        if ($user->merchantProfile) {
            $merchantFillable = (new MerchantProfile())->getFillable();
            $merchantData = array_intersect_key($data, array_flip($merchantFillable));
            if (!empty($merchantData)) {
                $user->merchantProfile->update($merchantData);
            }
        }

        // 3. Nạp lại model kèm theo các quan hệ để đảm bảo Accessors có dữ liệu mới nhất
        return $user->refresh()->load(['customerProfile', 'driverProfile', 'merchantProfile']);
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
    public function incrementOtpAttempts(UserOtp $userOtp): UserOtp
    {
        $userOtp->increment('attempts');
        return $userOtp->fresh();
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
