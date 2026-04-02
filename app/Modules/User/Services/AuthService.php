<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\User\Interfaces\AuthServiceInterface;
use App\Modules\User\Model\Enums\Gender;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\User;
use App\Modules\User\Model\UserOtp;
use App\Modules\User\Repositories\UserOtpRepository;
use App\Modules\User\Repositories\UserRepository;

class AuthService extends BaseService implements AuthServiceInterface
{
    protected const RETRY_AFTER_SECONDS = 60;
    protected const MAX_SEND_PER_DAY = 5;
    protected const OTP_TTL_MINUTES = 5;
    protected const MAX_OTP_ATTEMPTS = 5;

    public function __construct(
        protected UserRepository    $userRepository,
        protected UserOtpRepository $userOtpRepository,
    )
    {
    }

    /**
     * POST /authenticate-otp
     * Validate context (đăng ký vs đăng nhập) → throttle → dispatch OTP.
     */
    public function sendOtp(string $phone, UserOtpType $type): ServiceReturn
    {
        return $this->execute(function () use ($phone, $type) {
            // 1. Validate số điện thoại theo luồng
            $this->assertPhoneEligibleForOtp($phone, $type);

            // 2. Throttle + tạo OTP + gửi SMS
            $otpRecord = $this->dispatchOtp($phone, $type);

            return [
                'retry_after_seconds' => self::RETRY_AFTER_SECONDS,
                'expires_at' => $otpRecord->expired_at,
                'plain_code' => $otpRecord->plain_code,
            ];
        });
    }

    /**
     * POST /register
     * Verify OTP → tạo user + profile + device → trả token.
     */
    public function register(array $data): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($data) {
                // Verify OTP
                $this->verifyOtpOrFail(
                    $data['phone'],
                    $data['otp'],
                    UserOtpType::VERIFY_REGISTER
                );
                // Check exist
                $checkExist = $this->userRepository->query()
                    ->where('phone', $data['phone'])
                    ->withTrash()
                    ->first();

                if ($checkExist) {
                    $this->throw('Số điện thoại đã được đăng ký.', 409);
                }

                // Create user
                /**
                 * @var User $user
                 */
                $user = $this->userRepository->create([
                    'phone' => $data['phone'],
                    'password' => bcrypt($data['password'] ?? \Str::random(16)),
                    'role' => UserRole::Customer, // Mặc định là khách hàng khi đăng ký
                    'is_verified' => true,
                    'is_phone_verified' => true,
                    'is_active' => true,
                ]);

                // 3. Profile
                $this->userRepository->createCustomerProfile($user->id, [
                    'full_name' => $data['full_name'],
                    'gender' => Gender::Other->value,
                ]);

                // 4. Device
                $this->upsertDeviceIfPresent($user, $data);

                // 5. Consume OTP
                $this->userOtpRepository->markLatestAsUsed(
                    $data['phone'],
                    UserOtpType::VERIFY_REGISTER,
                );

                // 6. Token
                $token = $this->generateTokenAuth($user);

                return [
                    'user' => $user->load('customerProfile'),
                    'token' => $token,
                ];
            });
    }

    /**
     * POST /login
     * Verify OTP → kiểm tra trạng thái tài khoản → trả token.
     */
    public function login(array $data): ServiceReturn
    {
        return $this->execute(function () use ($data) {
            $phone = $data['phone'];

            // 1. Kiểm tra user tồn tại
            $user = $this->userRepository->findByPhone($phone);
            if (!$user) {
                $this->throw('Số điện thoại chưa được đăng ký trong hệ thống.', 404);
            }

            // 2. Verify OTP
            $this->verifyOtpOrFail($phone, $data['otp'], UserOtpType::VERIFY_LOGIN);

            // 3. Kiểm tra tài khoản có bị khoá không
            if (!$user->is_active) {
                $this->throw('Tài khoản đã bị khoá, vui lòng liên hệ hỗ trợ.', 403);
            }

            // 4. Cập nhật device
            $this->upsertDeviceIfPresent($user, $data);

            // 5. Consume OTP
            $this->userOtpRepository->markLatestAsUsed($phone, UserOtpType::VERIFY_LOGIN);

            // 6. Token
            $token = $this->generateTokenAuth($user);

            switch ($user->role) {
                case UserRole::Customer:
                    $profile = $user->load('customerProfile');
                    break;
                case UserRole::Merchants:
                    $profile = $user->load('merchantProfile');
                    break;
                case UserRole::Admin:
                    $profile = $user->load('adminProfile');
                    break;
                case UserRole::Driver:
                    $profile = $user->load('driverProfile');
                    break;
            }
            return [
                'profile' => $profile,
                'token' => $token,
            ];
        }, useTransaction: true);
    }

    /**
     * POST /logout
     */
    public function logout(User $user, bool $logoutAll = false): ServiceReturn
    {
        return $this->execute(function () use ($user, $logoutAll) {
            if ($logoutAll) {
                $user->tokens()->delete();
            } else {
                $user->currentAccessToken()->delete();
            }
        });
    }

    /**
     * Thực sự tạo OTP record và gửi SMS.
     * Protected để subclass (VD: DriverAuthService) có thể gọi lại
     * mà không cần lặp logic throttle.
     *
     * Luồng:
     *   throttle theo RETRY_AFTER_SECONDS
     *   → giới hạn MAX_SEND_PER_DAY
     *   → generateOtp (hash + persist)
     *   → gửi SMS
     */
    protected function dispatchOtp(string $phone, UserOtpType $type): UserOtp
    {
        // Throttle: chặn gửi lại quá nhanh
        $lastOtp = $this->userOtpRepository->getLastOtp($phone, $type);

        if ($lastOtp && $lastOtp->created_at->addSeconds(self::RETRY_AFTER_SECONDS)->isFuture()) {
            $retryAfter = now()->diffInSeconds(
                $lastOtp->created_at->addSeconds(self::RETRY_AFTER_SECONDS)
            );
            $this->throw("Vui lòng đợi {$retryAfter} giây trước khi yêu cầu mã mới.", 429);
        }

        // Giới hạn số lần gửi trong ngày
        $sentToday = $this->userOtpRepository->countSentToday($phone, $type);
        if ($sentToday >= self::MAX_SEND_PER_DAY) {
            $this->throw('Bạn đã gửi OTP quá số lần cho phép trong ngày.', 429);
        }

        // Tạo OTP
        $otpRecord = $this->userOtpRepository->generateOtp($phone, $type);

        // TODO: gửi SMS
        // $this->smsProvider->send($phone, $otpRecord->plain_code);

        return $otpRecord;
    }

    /**
     * Xác minh OTP — throw nếu sai / hết hạn / đã dùng.
     * Protected để subclass override nếu cần thêm logic (VD: bypass OTP ở môi trường test).
     */
    protected function verifyOtpOrFail(string $phone, string $code, UserOtpType $type): void
    {
        $otpRecord = $this->userOtpRepository->getLastOtp($phone, $type);

        if (!$otpRecord) {
            $this->throw('Mã OTP không tồn tại.', 400);
        }

        if ($otpRecord->used_at !== null) {
            $this->throw('Mã OTP đã được sử dụng.', 400);
        }

        if ($otpRecord->isExpired()) {
            $this->throw('Mã OTP đã hết hạn.', 400);
        }

        if ($otpRecord->attempts >= self::MAX_OTP_ATTEMPTS) {
            $this->throw('Mã OTP đã hết số lần thử. Vui lòng yêu cầu mã mới.', 400);
        }

        if (!$otpRecord->checkCode($code)) {
            $this->userOtpRepository->incrementAttempts($otpRecord);
            $this->throw('Mã OTP không chính xác.', 400);
        }

        $this->userOtpRepository->markAsVerified($otpRecord);
    }

    /**
     * Validate số điện thoại có đủ điều kiện nhận OTP không.
     * - VERIFY_REGISTER : số chưa tồn tại
     * - VERIFY_LOGIN    : số đã tồn tại
     */
    private function assertPhoneEligibleForOtp(string $phone, UserOtpType $type): void
    {
        $exists = $this->userRepository->existsByPhone($phone);

        switch ($type) {
            case UserOtpType::VERIFY_REGISTER:
                if ($exists) {
                    $this->throw('Số điện thoại đã được đăng ký.', 409);
                }
                break;

            case UserOtpType::VERIFY_LOGIN:
            case UserOtpType::VERIFY_FORGOT_PASSWORD:
                if (!$exists) {
                    $this->throw('Số điện thoại chưa được đăng ký trong hệ thống.', 404);
                }
                break;

            default:
                $this->throw('Loại OTP không hợp lệ.', 400);
        }
    }

    /**
     * Tạo token cho user.
     * @param User $user
     * @return string
     */
    private function generateTokenAuth(User $user)
    {
        return $user->createToken(
            name: 'auth_token',
            abilities: ['*'],
            expiresAt: now()->addMonth())->plainTextToken;
    }

    /**
     * Upsert device nếu request có gửi device_id.
     */
    private function upsertDeviceIfPresent(User $user, array $data): void
    {
        if (empty($data['device_id'])) {
            return;
        }

        $this->userRepository->upsertDevice($user, [
            'device_id' => $data['device_id'],
            'token' => $data['device_token'],
            'device_type' => $data['device_type'] ?? null,
        ]);
    }
}
