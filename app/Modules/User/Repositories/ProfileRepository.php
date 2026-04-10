<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Core\Repository\BaseRepository;
use App\Modules\User\Interfaces\ProfileRepositoryInterface;
use App\Modules\User\Model\CustomerProfile;
use App\Modules\User\Model\DriverProfile;
use App\Modules\User\Model\MerchantProfile;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\User;
use App\Modules\User\Model\UserOtp;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProfileRepository extends BaseRepository implements ProfileRepositoryInterface
{
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
        return DB::transaction(
            function () use ($user, $data) {
            // 1. Cập nhật bảng users (chỉ các trường account/system)
            $userFillable = $user->getFillable();
            $userData = array_intersect_key($data, array_flip($userFillable));

            if (!empty($userData)) {
                // LOGIC MỚI: Kiểm tra nếu số điện thoại thay đổi thì hủy trạng thái verified
                if (isset($userData['phone']) && $userData['phone'] !== $user->phone) {
                    $userData['is_phone_verified'] = false;
                }

                $user->update($userData);
            }

            // 2. Cập nhật profile tương ứng dựa trên các quan hệ hiện có
            $this->updateRelatedProfiles($user, $data);

            // 3. Nạp lại model kèm theo các quan hệ
            return $user->refresh()->load(['customerProfile', 'driverProfile', 'merchantProfile']);
        });
    }

    /**
     * Gom nhóm logic cập nhật các bảng profile liên quan
     */
    private function updateRelatedProfiles(User $user, array $data): void
    {
        // Customer Profile
        if ($user->customerProfile) {
            $customerFillable = $user->customerProfile->getFillable();
            $customerData = array_intersect_key($data, array_flip($customerFillable));
            if (!empty($customerData)) {
                $this->updateCustomerProfile($user, $customerData);
            }
        }

        // Driver Profile
        if ($user->driverProfile) {
            $driverFillable = $user->driverProfile->getFillable();
            $driverData = array_intersect_key($data, array_flip($driverFillable));
            if (!empty($driverData)) {
                $user->driverProfile->update($driverData);
            }
        }

        // Merchant Profile
        if ($user->merchantProfile) {
            $merchantFillable = $user->merchantProfile->getFillable();
            $merchantData = array_intersect_key($data, array_flip($merchantFillable));
            if (!empty($merchantData)) {
                $user->merchantProfile->update($merchantData);
            }
        }
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
