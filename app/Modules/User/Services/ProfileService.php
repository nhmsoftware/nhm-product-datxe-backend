<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\User\Interfaces\ProfileRepositoryInterface;
use App\Modules\User\Interfaces\ProfileServiceInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;

class ProfileService extends BaseService implements ProfileServiceInterface
{
    private const SENSITIVE_FIELDS = ['phone', 'email'];
    private const MAX_OTP_ATTEMPTS = 5;

    public function __construct(
        protected ProfileRepositoryInterface $profileRepository,
        protected UserRepositoryInterface    $userRepository
    )
    {
    }


    /**
     * Lấy hồ sơ người dùng
     */
    public function getProfile(User $user): ServiceReturn
    {
        return $this->execute(function () use ($user) {
            if ($user->isLocked()) {
                $this->throw('Tài khoản của bạn đã bị khóa.', 403);
            }

            return $user;
        });
    }

    /**
     * Cập nhật hồ sơ người dùng
     */
    public function updateProfile(User $user, array $data): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($user, $data) {

            if ($user->isLocked()) {
                $this->throw('Tài khoản của bạn đã bị khóa.', 403);
            }

            // 1. Tách dữ liệu
            $sensitiveData = array_intersect_key($data, array_flip(self::SENSITIVE_FIELDS));
            $nonSensitiveData = array_diff_key($data, $sensitiveData);

            // 4. Update non-sensitive
            if (!empty($nonSensitiveData)) {
                $this->profileRepository->updateUser($user, $nonSensitiveData);
                $this->profileRepository->updateProfiles($user, $nonSensitiveData);
            }

            // 5. Check thay đổi sensitive
            $changedFields = $this->getChangedSensitiveFields($user, $sensitiveData);

            if (!empty($changedFields)) {
                return $this->success(
                    data: $user->refresh(),
                    message: 'Cần xác thực OTP cho: ' . implode(', ', $changedFields)
                );
            }

            return $user->refresh();

        }, useTransaction: true);
    }


    /**
     * Lấy danh sách thay đổi trường sensible
     */
    private function getChangedSensitiveFields(User $user, array $data): array
    {
        $changed = [];

        foreach (self::SENSITIVE_FIELDS as $field) {
            if (isset($data[$field]) && $data[$field] !== $user->{$field}) {
                $changed[] = $field;
            }
        }

        return $changed;
    }


    /**
     * Xác thực và cập nhật trường sensible
     */
    public function verifyAndUpdateSensitiveFields(User $user, string $otp, array $sensitiveData): ServiceReturn
    {
        $userOtp = $this->profileRepository->findValidOtp(
            $user->phone,
            UserOtpType::CHANGE_PROFILE
        );

        $otpValid = $userOtp?->checkCode($otp);

        if ($userOtp && !$otpValid) {
            $userOtp = $this->profileRepository->incrementOtpAttempts($userOtp);
        }

        return $this->execute(function () use ($user, $sensitiveData, $userOtp, $otpValid) {

            // 1. Validate OTP
            if (!$userOtp) {
                $this->throw('OTP không hợp lệ hoặc hết hạn.', 400);
            }

            if ($userOtp->attempts >= self::MAX_OTP_ATTEMPTS) {
                $this->throw('OTP bị khóa do nhập sai nhiều lần.', 400);
            }

            if (!$otpValid) {
                $remain = self::MAX_OTP_ATTEMPTS - $userOtp->attempts;
                $this->throw("OTP sai. Còn {$remain} lần.", 400);
            }

            // 2. Validate phone unique
            if (isset($sensitiveData['phone'])) {

                if ($sensitiveData['phone'] === $user->phone) {
                    $this->throw('SĐT không được đổi.', 400);
                }

                $existingUser = $this->userRepository->findByPhone($sensitiveData['phone']);

                if ($existingUser && $existingUser->id !== $user->id) {
                    $this->throw('SĐT đã tồn tại.', 422);
                }

                // reset verify
                $sensitiveData['is_phone_verified'] = false;
            }

            // 3. Validate email unique
            if (isset($sensitiveData['email'])) {

                if ($sensitiveData['email'] === $user->email) {
                    $this->throw('Email không được đổi.', 400);
                }

                $existingUser = $this->userRepository->findByEmail($sensitiveData['email']);

                if ($existingUser && $existingUser->id !== $user->id) {
                    $this->throw('Email đã tồn tại.', 422);
                }
            }

            // 4. Mark OTP used
            $this->profileRepository->markOtpAsVerified($userOtp);

            // 5. Update user (bảng users)
            $this->profileRepository->updateUser($user, $sensitiveData);

            return $user->refresh();

        }, useTransaction: true);
    }

    /**
     * Đổi mật khẩu
     */
    public function changePassword(User $user, string $newPassword): ServiceReturn
    {
        return $this->execute(function () use ($user, $newPassword) {

            $this->profileRepository->updateUser($user, [
                'password' => Hash::make($newPassword),
            ]);

            return true;

        }, useTransaction: true);
    }
}
