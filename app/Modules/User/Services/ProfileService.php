<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\User\DTO\UpdateProfileDTO;
use App\Modules\User\Interfaces\ProfileRepositoryInterface;
use App\Modules\User\Interfaces\ProfileServiceInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\User;
use Illuminate\Support\Facades\Hash;

final class ProfileService extends BaseService implements ProfileServiceInterface
{
    private const SENSITIVE_FIELDS  = ['phone', 'email'];
    private const MAX_OTP_ATTEMPTS  = 5;

    public function __construct(
        protected ProfileRepositoryInterface $profileRepository,
        protected UserRepositoryInterface    $userRepository,
    ) {}


    /**
     * Lấy hồ sơ người dùng.
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
     * Cập nhật hồ sơ người dùng.
     */
    public function updateProfile(UpdateProfileDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy người dùng.', 404);
            
            if ($user->isLocked()) {
                $this->throw('Tài khoản của bạn đã bị khóa.', 403);
            }
            
            $data = $dto->data;

            // 1. Tách dữ liệu sensitive / non-sensitive
            $sensitiveData    = array_intersect_key($data, array_flip(self::SENSITIVE_FIELDS));
            $nonSensitiveData = array_diff_key($data, $sensitiveData);

            // 2. Update non-sensitive fields
            if (!empty($nonSensitiveData)) {
                $userData = array_intersect_key($nonSensitiveData, array_flip($user->getFillable()));
                $this->profileRepository->updateUser($user, $userData);
                $this->profileRepository->updateProfiles($user, $nonSensitiveData);
            }

            // 3. Kiểm tra sensitive fields có thực sự thay đổi không
            $changedFields = $this->getChangedSensitiveFields($user, $sensitiveData);

            if (!empty($changedFields)) {
                return $this->success(
                    data: $user->refresh(),
                    message: 'Cần xác thực OTP cho: ' . implode(', ', $changedFields),
                );
            }

            return $user->refresh();
        }, useTransaction: true);
    }


    /**
     * Xác thực OTP và cập nhật sensitive fields.
     */
    public function verifyAndUpdateSensitiveFields(User $user, string $otp, array $sensitiveData): ServiceReturn
    {
        // 1. Xác thực OTP - Chạy ngoài transaction của phần update để tránh rollback attempts
        $verifyResult = $this->execute(function () use ($user, $otp, $sensitiveData) {
            if (empty($sensitiveData)) {
                $this->throw('Không có dữ liệu cần cập nhật.', 400);
            }

            // Luôn dùng số điện thoại cũ để xác thực OTP
            $phoneToVerify = $user->phone;

            $userOtp = $this->profileRepository->findValidOtpByPhone($phoneToVerify, UserOtpType::CHANGE_PROFILE);

            if (!$userOtp) {
                $this->throw('OTP không hợp lệ hoặc đã hết hạn.', 400);
            }

            if ($userOtp->attempts >= self::MAX_OTP_ATTEMPTS) {
                $this->throw('OTP bị khóa do nhập sai quá nhiều lần. Vui lòng tạo lại OTP mới.', 400);
            }

            if (!$userOtp->checkCode($otp)) {
                // Tăng số lần thử
                $userOtp = $this->profileRepository->incrementOtpAttempts($userOtp);
                $remain = self::MAX_OTP_ATTEMPTS - $userOtp->attempts;

                if ($remain <= 0) {
                    $this->throw('OTP bị khóa do nhập sai quá nhiều lần. Vui lòng tạo lại OTP mới.', 400);
                }

                $this->throw("OTP không đúng. Còn {$remain} lần thử.", 400);
            }

            return $userOtp;
        });

        if ($verifyResult->isError()) {
            return $verifyResult;
        }

        $userOtp = $verifyResult->getData();

        // 2. Thực hiện cập nhật trong transaction
        return $this->execute(function () use ($user, $userOtp, $sensitiveData) {
            // Validate số điện thoại mới (nếu có)
            if (isset($sensitiveData['phone'])) {
                if ($sensitiveData['phone'] === $user->phone) {
                    $this->throw('Số điện thoại mới phải khác số hiện tại.', 400);
                }

                $existingUser = $this->userRepository->findByPhone($sensitiveData['phone']);
                if ($existingUser && $existingUser->id !== $user->id) {
                    $this->throw('Số điện thoại đã được sử dụng bởi tài khoản khác.', 422);
                }

                $sensitiveData['is_phone_verified'] = true; // Đã verify OTP rồi nên set true
            }

            // Validate email unique (nếu có)
            if (isset($sensitiveData['email'])) {
                if ($sensitiveData['email'] === $user->email) {
                    $this->throw('Email mới phải khác email hiện tại.', 400);
                }

                $existingUser = $this->userRepository->findByEmail($sensitiveData['email']);
                if ($existingUser && $existingUser->id !== $user->id) {
                    $this->throw('Email đã được sử dụng bởi tài khoản khác.', 422);
                }
            }

            // Mark OTP đã dùng
            $this->profileRepository->markOtpAsUsed($userOtp);

            // Cập nhật sensitive fields
            $this->profileRepository->updateUser($user, $sensitiveData, allowSensitive: true);

            return $user->refresh();
        }, useTransaction: true);
    }


    /**
     * Thay đổi mật khẩu.
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


    /**
     * Trả về danh sách sensitive fields thực sự thay đổi giá trị.
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
}
