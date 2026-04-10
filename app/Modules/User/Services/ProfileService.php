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
    private const SENSITIVE_FIELDS = ['phone'];

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
                $this->throw(message: 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.', code: 403);
            }

            return $user;
        });
    }

    /**
     * Update user profile with role-specific fields.
     */
    public function updateProfile(User $user, array $data): ServiceReturn
    {
        return $this->execute(function () use ($user, $data) {
            // 1. Phân loại trường nhạy cảm và không nhạy cảm
            $sensitiveData = array_intersect_key($data, array_flip(self::SENSITIVE_FIELDS));
            $nonSensitiveData = array_diff_key($data, $sensitiveData);

            // 2. Cập nhật các trường không nhạy cảm trước
            if (!empty($nonSensitiveData)) {
                $user = $this->profileRepository->updateProfile($user, $nonSensitiveData);
            }

            // 3. Kiểm tra thay đổi số điện thoại (trường nhạy cảm duy nhất hiện tại)
            $sensitiveChanges = $this->getChangedSensitiveFields($user, $sensitiveData);

            if (!empty($sensitiveChanges)) {
                // Nếu có thay đổi SĐT, trả về mã 202 kèm model đã update thông thường
                return $this->success(
                    data: $user,
                    message: 'Thông tin cá nhân đã được cập nhật. Riêng số điện thoại mới cần xác thực OTP để thay đổi.',
                );
            }

            // Trả về model User đã cập nhật hoàn toàn (hoặc chỉ phần không nhạy cảm)
            return $user;

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

        // Tìm OTP hợp lệ (Xác thực ngoài transaction để tránh rollback attempts)
        $userOtp = $this->profileRepository->findValidOtp($user->phone, UserOtpType::CHANGE_PROFILE);
        return $this->execute(function () use ($user, $otp, $sensitiveData, $userOtp) {

            if (!$user->is_active) {
                $this->throw(message: 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.', code: 403);
            }

            if (empty($sensitiveData)) {
                $this->throw(message: 'Không có trường nhạy cảm nào cần cập nhật.', code: 400);
            }

            if ($sensitiveData['phone'] === $user->phone) {
                $this->throw(message: 'SĐT mới không thay đổi.', code: 400);
            }


            if (!$userOtp) {
                return $this->throw(message: 'Mã OTP không hợp lệ hoặc đã hết hạn.', code: 400);
            }

            if ($userOtp->attempts >= self::MAX_OTP_ATTEMPTS) {
                $this->throw(message: 'Bạn đã nhập sai mã OTP quá ' . self::MAX_OTP_ATTEMPTS . ' lần. Mã này đã bị khóa, vui lòng yêu cầu mã mới.', code: 400);
            }

            if (!$userOtp->checkCode($otp)) {
                $this->profileRepository->incrementOtpAttempts($userOtp);

                if ($userOtp->attempts >= self::MAX_OTP_ATTEMPTS) {
                    $this->throw(message: 'Bạn đã nhập sai mã OTP quá ' . self::MAX_OTP_ATTEMPTS . ' lần.', code: 400);
                }

                $remaining = self::MAX_OTP_ATTEMPTS - $userOtp->attempts;
                $this->throw(message: "Mã OTP không đúng. Bạn còn {$remaining} lần thử.", code: 400);
            }

            // Đánh dấu OTP đã được xác thực
            $this->profileRepository->markOtpAsVerified($userOtp);

            // Cập nhật các trường nhạy cảm
            $user = $this->profileRepository->updateProfile($user, $sensitiveData);

            return $user;
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
}
