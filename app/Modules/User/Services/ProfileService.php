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
    // Sensitive fields that require OTP verification
    private const SENSITIVE_FIELDS = ['phone', 'email'];

    private const MAX_OTP_ATTEMPTS = 5;

    public function __construct(
        protected ProfileRepositoryInterface $profileRepository,
        protected UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Get user profile with role-specific details.
     */
    public function getProfile(User $user): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($user) {
            if ($user->isLocked()) {
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

            if ($data['phone'] && $this->userRepository->findByPhone($data['phone']) && $data['phone'] !== $user->phone) {
                $this->throw(message: 'SĐT đã tồn tại trên hệ thống.', code: 422);
            }

            if ($data['email'] && $this->userRepository->findByEmail($data['email']) && $data['email'] !== $user->email) {
                 $this->throw(message: 'Email đã tồn tại trên hệ thống.', code: 422);
            }

            if (!empty($nonSensitiveData)) {
                try {
                    $user = $this->profileRepository->updateProfile($user, $nonSensitiveData);
                } catch (QueryException $e) {
                    $this->throw(message: 'Lỗi trong hệ thống: ' . $e->getMessage(), code: 422);
                }
            }

            // 3. Kiểm tra thay đổi số điện thoại hoặc email
            $sensitiveChanges = $this->getChangedSensitiveFields($user, $sensitiveData);

            if (!empty($sensitiveChanges)) {
                // Nếu có thay đổi, trả về mã 202 kèm model đã update thông thường
                return $this->success(
                    data: $user,
                    message: 'Thông tin cá nhân đã được cập nhật. Các trường (' . implode(', ', $sensitiveChanges) . ') yêu cầu xác thực OTP để thay đổi.',
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

        // Kiểm tra OTP
        $otpValid = $userOtp?->checkCode($otp);
        if ($userOtp && !$otpValid) {
            $userOtp = $this->profileRepository->incrementOtpAttempts($userOtp);
        }
        return $this->execute(function () use ($user, $otp, $sensitiveData, $userOtp, $otpValid) {

            if ($user->isLocked()) {
                $this->throw(message: 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ hỗ trợ.', code: 403);
            }

            if (empty($sensitiveData)) {
                $this->throw(message: 'Không có trường nhạy cảm nào cần cập nhật.', code: 400);
            }

            if (isset($sensitiveData['phone']) && $this->userRepository->findByPhone($sensitiveData['phone'])) {
                $this->throw(message: 'SĐT đã tồn tại trên hệ thống.', code: 422);
            }

            if (isset($sensitiveData['email']) && $this->userRepository->findByEmail($sensitiveData['email'])) {
                $this->throw(message: 'Email đã tồn tại trên hệ thống.', code: 422);
            }

            if (isset($sensitiveData['phone']) && $sensitiveData['phone'] === $user->phone) {
                $this->throw(message: 'SĐT mới không thay đổi.', code: 400);
            }

            if (isset($sensitiveData['email']) && $sensitiveData['email'] === $user->email) {
                $this->throw(message: 'Email mới không thay đổi.', code: 400);
            }


            if (!$userOtp) {
                return $this->throw(message: 'Mã OTP không hợp lệ hoặc đã hết hạn.', code: 400);
            }

            if ($userOtp->attempts >= self::MAX_OTP_ATTEMPTS) {
                $this->throw(message: 'Bạn đã nhập sai mã OTP quá ' . self::MAX_OTP_ATTEMPTS . ' lần. Mã này đã bị khóa, vui lòng yêu cầu mã mới.', code: 400);
            }

            if (!$otpValid) {
                $remaining = self::MAX_OTP_ATTEMPTS - $userOtp->attempts;

                if ($remaining <= 0) {
                    $this->throw(message: 'Bạn đã nhập sai mã OTP quá ' . self::MAX_OTP_ATTEMPTS . ' lần.', code: 400);
                }

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
