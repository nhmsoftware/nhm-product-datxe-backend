<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\User\Interfaces\ProfileRepositoryInterface;
use App\Modules\User\Interfaces\ProfileServiceInterface;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\User;
use Illuminate\Support\Facades\Hash;
class ProfileService extends BaseService implements ProfileServiceInterface
{
    // Sensitive fields that require OTP verification
    private const SENSITIVE_FIELDS = ['phone', 'email'];

    private const MAX_OTP_ATTEMPTS = 5;

    public function __construct(
        private readonly ProfileRepositoryInterface $profileRepository
    ) {
    }

    /**
     * Get user profile with role-specific details.
     */
    public function getProfile(User $user): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($user) {
            if (!$user->is_active) {
                $this->throw('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.', 403);
            }

            $profile = $this->buildBaseProfile($user);

            return match ($user->role) {
                UserRole::Customer => $this->buildCustomerProfile($profile, $user),
                UserRole::Driver => $this->buildDriverProfile($profile, $user),
                UserRole::Merchants => $this->buildMerchantProfile($profile, $user),
                default => $profile,
            };
        });
    }

    /**
     * Update user profile with role-specific fields.
     */
    public function updateProfile(User $user, array $data): ServiceReturn
    {
        return $this->execute(function () use ($user, $data) {
            if (!$user->is_active) {
                $this->throw('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.', 403);
            }

            // Check for sensitive field changes
            $sensitiveChanges = $this->getChangedSensitiveFields($user, $data);

            // If there are sensitive changes, they need OTP verification
            if (!empty($sensitiveChanges)) {
                $this->throw(
                    'Có thay đổi thông tin nhạy cảm. Vui lòng xác thực OTP.',
                    422,
                    ['requires_otp' => true, 'sensitive_fields' => $sensitiveChanges]
                );
            }

            // Update non-sensitive fields
            $user = $this->profileRepository->updateProfile($user, $data);

            return $this->buildProfile($user);
        }, useTransaction: true);
    }

    /**
     * Check if sensitive fields (phone, email) have changed.
     */
    public function getChangedSensitiveFields(User $user, array $data): array
    {
        $changedFields = [];

        foreach (self::SENSITIVE_FIELDS as $field) {
            if (isset($data[$field]) && $data[$field] !== $user->{$field}) {
                $changedFields[] = $field;
            }
        }

        return $changedFields;
    }

    /**
     * Xác minh OTP và cập nhật các trường nhạy cảm.
     */
    public function verifyAndUpdateSensitiveFields(User $user, string $otp, array $sensitiveData): ServiceReturn
    {
        if (!$user->is_active) {
            $this->throw('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.', 403);
        }

        // Tìm OTP hợp lệ (Xác thực ngoài transaction để tránh rollback attempts)
        $userOtp = $this->profileRepository->findValidOtp($user->phone, UserOtpType::CHANGE_PROFILE);

        if (!$userOtp) {
            $this->throw('Mã OTP không hợp lệ hoặc đã hết hạn.', 400);
        }

        if ($userOtp->attempts >= self::MAX_OTP_ATTEMPTS) {
            $this->throw('Bạn đã nhập sai mã OTP quá' . self::MAX_OTP_ATTEMPTS . 'lần. Mã này đã bị khóa, vui lòng yêu cầu mã mới.', 400);
        }

        if (!$userOtp->checkCode($otp)) {
            $this->profileRepository->incrementOtpAttempts($userOtp);

            if ($userOtp->attempts >= self::MAX_OTP_ATTEMPTS) {
                $this->throw('Bạn đã nhập sai mã OTP quá' . self::MAX_OTP_ATTEMPTS . 'lần.', 400);
            }

            $remaining = self::MAX_OTP_ATTEMPTS - $userOtp->attempts;
            $this->throw("Mã OTP không đúng. Bạn còn {$remaining}.", 400);
        }

        return $this->execute(function () use ($user, $userOtp, $sensitiveData) {
            // Đánh dấu OTP đã được xác thực
            $this->profileRepository->markOtpAsVerified($userOtp);

            // Cập nhật các trường nhạy cảm
            $user = $this->profileRepository->updateProfile($user, $sensitiveData);

            return $this->buildProfile($user);
        }, useTransaction: true);
    }

    /**
     * Change user password.
     */
    public function changePassword(User $user, string $newPassword): ServiceReturn
    {
        return $this->execute(function () use ($user, $newPassword) {
            $this->profileRepository->updateProfile($user, [
                'password' => Hash::make($newPassword),
            ]);

            return true;
        }, useTransaction: true);
    }

    /**
     * Xây dựng dữ liệu hồ sơ hoàn chỉnh.
     */
    private function buildProfile(User $user): array
    {
        $profile = $this->buildBaseProfile($user);

        return match ($user->role) {
            UserRole::Customer => $this->buildCustomerProfile($profile, $user),
            UserRole::Driver => $this->buildDriverProfile($profile, $user),
            UserRole::Merchants => $this->buildMerchantProfile($profile, $user),
            default => $profile,
        };
    }

    /**
     * Xây dựng dữ liệu hồ sơ cơ bản chung cho tất cả các vai trò.
     */
    private function buildBaseProfile(User $user): array
    {
        return [
            'id' => $user->id,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
            'avatar' => $user->avatar ?? null,
            'full_name' => $user->full_name ?? null,
            'phone' => $user->phone,
            'email' => $user->email ?? null,
            'gender' => $user->gender?->value ?? null,
            'gender_label' => $user->gender?->label() ?? null,
            'address' => $user->address ?? null,
            'citizen_id' => $user->citizen_id ?? null,
            'is_verified' => $user->is_verified,
            'is_phone_verified' => $user->is_phone_verified,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    /**
     * Xây dựng dữ liệu hồ sơ dành riêng cho khách hàng.
     */
    private function buildCustomerProfile(array $profile, User $user): array
    {
        $customerProfile = $user->customerProfile;

        $profile['customer_specific'] = [
            'birthday' => $customerProfile?->birthday?->toDateString() ?? null,
        ];

        return $profile;
    }

    /**
     * Xây dựng dữ liệu hồ sơ dành riêng cho tài xế.
     */
    private function buildDriverProfile(array $profile, User $user): array
    {
        $driverProfile = $user->driverProfile;

        $profile['driver_specific'] = [
            'full_name' => $driverProfile?->full_name ?? null,
            'vehicle_info' => [
                'name' => $driverProfile?->vehicle_name ?? null,
                'type' => $driverProfile?->vehicle_type ?? null,
                'color' => $driverProfile?->vehicle_color ?? null,
                'number' => $driverProfile?->vehicle_number ?? null,
            ],
            'license' => [
                'number' => $driverProfile?->license_number ?? null,
                'front_image' => $driverProfile?->license_front_image ?? null,
                'back_image' => $driverProfile?->license_back_image ?? null,
            ],
            'stats' => [
                'average_rating' => $driverProfile?->average_rating ?? 0,
                'total_trips' => $driverProfile?->total_trips ?? 0,
            ],
            'banking' => [
                'bank_name' => $driverProfile?->bank_name ?? null,
                'account_number' => $driverProfile?->bank_account_number ?? null,
                'account_holder' => $driverProfile?->bank_account_holder ?? null,
            ],
        ];

        return $profile;
    }

    /**
     * Xây dựng dữ liệu hồ sơ dành riêng cho đối tác.
     */
    private function buildMerchantProfile(array $profile, User $user): array
    {
        $merchantProfile = $user->merchantProfile;

        $profile['merchant_specific'] = [
            'store_name' => $merchantProfile?->store_name ?? null,
            'store_address' => $merchantProfile?->store_address ?? null,
            'store_latitude' => $merchantProfile?->latitude ?? null,
            'store_longitude' => $merchantProfile?->longitude ?? null,
            'opening_time' => $merchantProfile?->opening_time ?? null,
            'closing_time' => $merchantProfile?->closing_time ?? null,
            'is_open' => $merchantProfile?->is_open ?? true,
            'business_license' => $merchantProfile?->business_license ?? null,
            'business_license_image' => $merchantProfile?->business_license_image ?? null,
            'average_rating' => $merchantProfile?->average_rating ?? null,
            'total_orders' => $merchantProfile?->total_orders ?? 0,
        ];

        return $profile;
    }


}
