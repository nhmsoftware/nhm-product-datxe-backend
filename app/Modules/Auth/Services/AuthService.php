<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceException;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthOtpRepositoryInterface;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\Gender;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\User;
use App\Modules\User\Model\UserOtp;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PhpParser\Token;

class AuthService extends BaseService implements AuthServiceInterface
{
    protected const RETRY_AFTER_SECONDS = 180; // 3 phút
    protected const MAX_SEND_PER_DAY = 5;
    protected const MAX_OTP_ATTEMPTS = 5;

    public function __construct(
        protected UserRepositoryInterface    $userRepository,
        protected AuthOtpRepositoryInterface $authOtpRepository,
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

            $response = [
                'retry_after_seconds' => self::RETRY_AFTER_SECONDS,
                'expires_at' => $otpRecord->expired_at,
            ];

            if (config('services.otp_expose') === true && !app()->isProduction()) {
                $response['otp_code'] = $otpRecord->plain_code;
            }

            return $response;
        });
    }

    /**
     * POST /register
     * Verify OTP → tạo user + profile + device → trả token.
     */
    public function register(array $data): ServiceReturn
    {
        return $this->execute(function () use ($data) {
            $this->verifyOtpOrFail($data['phone'], $data['otp'], UserOtpType::VERIFY_REGISTER);

            if ($this->userRepository->existsByPhone($data['phone'])) {
                $this->throw('Số điện thoại đã được đăng ký.', 409);
            }

            $user = $this->userRepository->create([
                'phone' => $data['phone'],
                'password' => bcrypt($data['password'] ?? \Str::random(16)),
                'role' => UserRole::Customer,
                'is_verified' => true,
                'is_phone_verified' => true,
                'is_active' => true,
            ]);

            $this->userRepository->createCustomerProfile($user, [
                'full_name' => $data['full_name'],
                'gender' => $data['gender'] ?? Gender::Other->value,
            ]);

            $this->upsertDeviceIfPresent($user, $data);
            $this->authOtpRepository->markLatestAsUsed($data['phone'], UserOtpType::VERIFY_REGISTER);
            $token = $this->generateTokenAuth($user);

            return [
                'user' => $user->load('customerProfile'),
                'token' => $token,
            ];
        });
    }

    /**
     * POST /login
     * Kiểm tra thông tin đăng nhập (SĐT + Mật khẩu) → kiểm tra trạng thái tài khoản → trả token.
     */
    public function login(array $data): ServiceReturn
    {
        return $this->execute(function () use ($data) {
            $phone = $data['phone'];
            $password = $data['password'];

            $user = $this->userRepository->findByPhone($phone);

            if (!$user) {
                $this->throw('Số điện thoại chưa được đăng ký.', 404);
            }

            if (!\Hash::check($password, $user->password)) {
                $this->throw('Mật khẩu không chính xác.', 401);
            }

            if ($user->isLocked()) {
                $this->throw('Tài khoản này đã bị khóa. Vui lòng liên hệ hỗ trợ để được hỗ trợ.', 403);
            }

            $this->upsertDeviceIfPresent($user, $data);

            $token = $this->generateTokenAuth($user);

            $user->load($this->getProfileRelation($user->role));
            return [
                'user' => $user,
                'token' => $token,
            ];
        }, useTransaction: true);
    }

    /**
     * POST /reset-password
     * Đặt lại mật khẩu với OTP
     * @param array $data
     * @return ServiceReturn
     */
    public function forgotPassword(array $data): ServiceReturn
    {
        $phone = $data['phone'];
        $user = $this->userRepository->findByPhone($phone);

        if (!$user) {
            $this->throw('Số điện thoại này chưa được đăng ký trên hệ thống.', 404);
        }

        if ($user->isLocked()) {
            $this->throw('Tài khoản này đã bị khóa. Vui lòng liên hệ hỗ trợ để được hỗ trợ.', 403);
        }

        if (Hash::check($data['password'], $user->password)) {
            return ServiceReturn::error(
                message: 'Mật khẩu mới không được trùng mật khẩu cũ',
            );
        }

        // Bước 1: Xác thực OTP ngoài transaction để record attempts không bị rollback
        $this->verifyOtpOrFail($phone, $data['otp'], UserOtpType::VERIFY_FORGOT_PASSWORD);

        // Bước 2: Thực hiện đổi mật khẩu trong transaction
        return $this->execute(function () use ($data, $user, $phone) {
            $this->userRepository->updateById($user->id, [
                'password' => bcrypt($data['password']),
            ]);

            $this->authOtpRepository->markLatestAsUsed($phone, UserOtpType::VERIFY_FORGOT_PASSWORD);

            $this->upsertDeviceIfPresent($user, $data);
            $token = $this->generateTokenAuth($user);
            $user->load($this->getProfileRelation($user->role));

            return [
                'user'  => $user,
                'token' => $token,
            ];
        }, useTransaction: true);
    }


    /**
     * POST /google-login
     * @param array $data
     * @return ServiceReturn
     */
    public function googleLogin(array $data): ServiceReturn
    {
        return $this->execute(
            callback:  function () use ($data) {
            $claims = $this->verifyGoogleToken($data['id_token']);
            $googleId = $claims['sub'];

            $user = $this->userRepository->findByGoogleId($googleId);

            if (!$user) {
                $email = $claims['email'] ?? null;
                $name = $claims['name'] ?? null;

                if (!$name) {
                    $name = $email ? explode('@', $email)[0] : 'User ' . $googleId;
                }

                if ($email && $this->userRepository->findByEmail($email)) {
                    $this->throw('Địa chỉ email đã được sử dụng bởi một tài khoản khác.', 409);
                }

                $user = $this->userRepository->create([
                    'google_id' => $googleId,
                    'email' => $email,
                    'password' => bcrypt(\Str::random(16)),
                    'role' => UserRole::Customer,
                    'is_verified' => true,
                    'is_active' => true,
                ]);
            }

            if (!$user->customerProfile) {
                $this->userRepository->createCustomerProfile($user, [
                    'full_name' => $name,
                    'gender' => Gender::Other->value,
                ]);
            }

            if ($user->isLocked()) {
                $this->throw('Tài khoản này đã bị khóa. Vui lòng liên hệ hỗ trợ để được hỗ trợ.', 403);
            }

            $this->upsertDeviceIfPresent($user, $data);
            $token = $this->generateTokenAuth($user);
            $user->load($this->getProfileRelation($user->role));

            return [
                'user' => $user,
                'token' => $token,
            ];
        }, useTransaction: true);
    }

    /**
     * POST /apple-login
     * @param array $data
     * @return ServiceReturn
     */
    public function appleLogin(array $data): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($data) {
            $appleConfig = config('services.apple');
            $claims = $this->verifyAppleToken($data['id_token'], $appleConfig);
            $appleId = $claims['sub'];

            $user = $this->userRepository->findByAppleId($appleId);

            if (!$user) {
                $email = $claims['email'] ?? null;
                $name = $this->extractAppleName($data['name'] ?? []);

                if (!$name) {
                    $name = $email ? explode('@', $email)[0] : 'User ' . $appleId;
                }

                if ($email && $this->userRepository->findByEmail($email)) {
                    $this->throw('Địa chỉ email đã được sử dụng bởi một tài khoản khác.', 409);
                }

                $user = $this->userRepository->create([
                    'apple_id' => $appleId,
                    'email' => $email,
                    'password' => bcrypt(\Str::random(16)),
                    'role' => UserRole::Customer,
                    'is_verified' => true,
                    'is_active' => true,
                ]);
            }

            if (!$user->customerProfile) {
                $this->userRepository->createCustomerProfile($user, [
                    'full_name' => $name,
                    'gender' => Gender::Other->value,
                ]);
            }

            if ($user->isLocked()) {
                $this->throw('Tài khoản này đã bị khóa. Vui lòng liên hệ hỗ trợ để được hỗ trợ.', 403);
            }

            $this->upsertDeviceIfPresent($user, $data);
            $token = $this->generateTokenAuth($user);
            $user->load($this->getProfileRelation($user->role));

            return [
                'user' => $user,
                'token' => $token,
            ];
        }, useTransaction: true);
    }

    /**
     * POST /logout
     * @param User $user
     * @param Token $tokenId
     * @param bool $logoutAll
     * @return ServiceReturn
     * @throws \Throwable
     */
    public function logout(User $user, bool $logoutAll = false): ServiceReturn
    {
        return $this->execute(function () use ($user, $logoutAll) {
            if ($logoutAll) $user->tokens()->delete();
            else $user->currentAccessToken()->delete();
        });
    }

    /**
     * Lấy relation profile theo role.
     * @param UserRole $role
     * @return string
     */
    private function getProfileRelation(UserRole $role): string
    {
        return match ($role) {
            UserRole::Customer => 'customerProfile',
            UserRole::Merchants => 'merchantProfile',
            UserRole::Admin => 'adminProfile',
            UserRole::Driver => 'driverProfile',
        };
    }

    /**
     * Gử OTP đến số điện thoại.
     * @param string $phone
     * @param UserOtpType $type
     * @return UserOtp
     * @throws ServiceException
     */
    protected function dispatchOtp(string $phone, UserOtpType $type): UserOtp
    {
        $lastOtp = $this->authOtpRepository->getLastOtp($phone, $type);
        if ($lastOtp && $lastOtp->created_at->addSeconds(self::RETRY_AFTER_SECONDS)->isFuture()) {
            $retryAfter = now()->diffInSeconds($lastOtp->created_at->addSeconds(self::RETRY_AFTER_SECONDS));
            $this->throw("Vui lòng đợi {$retryAfter} giây trước khi yêu cầu mã mới.", 429);
        }
        $sentToday = $this->authOtpRepository->countSentToday($phone, $type);
        if ($sentToday >= self::MAX_SEND_PER_DAY) $this->throw('Quá số lần OTP.', 429);
        $otpRecord = $this->authOtpRepository->generateOtp($phone, $type);
        return $otpRecord;
    }

    /**
     * Xác minh OTP — throw nếu sai / hết hạn / đã dùng.
     * Protected để subclass override nếu cần thêm logic (VD: bypass OTP ở môi trường test).
     * @param string $phone
     * @param string $code
     * @param UserOtpType $type
     * @return void
     * @throws ServiceException
     */
    protected function verifyOtpOrFail(string $phone, string $code, UserOtpType $type): void
    {
        $otpRecord = $this->authOtpRepository->getLastOtp($phone, $type);

        if (!$otpRecord) {
            $this->throw('Mã OTP không tồn tại.', 400);
        }

        if ($otpRecord->used_at) {
            $this->throw('Mã OTP đã sử dụng.', 400);
        }

        if ($otpRecord->isExpired()) {
            $this->throw('Mã OTP hết hạn.', 400);
        }

        if ($otpRecord->attempts >= self::MAX_OTP_ATTEMPTS) {
            $this->throw('Bạn đã nhập sai mã OTP quá ' . self::MAX_OTP_ATTEMPTS . ' lần. Mã này đã bị khóa, vui lòng yêu cầu mã mới.', 400);
        }

        if (!$otpRecord->checkCode($code)) {
            $this->authOtpRepository->incrementAttempts($otpRecord);

            if ($otpRecord->attempts >= self::MAX_OTP_ATTEMPTS) {
                $this->throw('Bạn đã nhập sai mã OTP quá ' . self::MAX_OTP_ATTEMPTS . ' lần. Mã này đã bị khóa, vui lòng yêu cầu mã mới.', 400);
            }

            $remaining = self::MAX_OTP_ATTEMPTS - $otpRecord->attempts;
            $this->throw("Mã OTP không chính xác. Bạn còn {$remaining} lần thử.", 400);
        }

        $this->authOtpRepository->markAsVerified($otpRecord);
    }

    /**
     * Validate số điện thoại có đủ điều kiện nhận OTP không.
     * - VERIFY_REGISTER : số chưa tồn tại
     * - VERIFY_LOGIN    : số đã tồn tại
     * - VERIFY_FORGOT_PASSWORD : số chưa tồn tại
     * @param string $phone
     * @param UserOtpType $type
     * @return void
     * @throws ServiceException
     */
    private function assertPhoneEligibleForOtp(string $phone, UserOtpType $type): void
    {
        $exists = $this->userRepository->existsByPhone($phone);
        switch ($type) {
            case UserOtpType::VERIFY_REGISTER:
                if ($exists) $this->throw('Số điện thoại đã đăng ký.', 409);
                break;
            case UserOtpType::VERIFY_LOGIN:
            case UserOtpType::VERIFY_FORGOT_PASSWORD:
            case UserOtpType::CHANGE_PROFILE:
                if (!$exists) $this->throw('Số điện thoại chưa đăng ký.', 404);
                break;
            default:
                $this->throw('Loại OTP không hợp lệ.', 400);
        }
    }

    /**
     * Tạo token auth cho user.
     * @param User $user
     * @return string
     */
    private function generateTokenAuth(User $user): string
    {
        return $user->createToken('auth_token', ['*'], now()->addMonth())->plainTextToken;
    }

    /**
     * Cập nhật device cho user nếu có.
     * @param User $user
     * @param array $data
     * @return void
     */
    private function upsertDeviceIfPresent(User $user, array $data): void
    {
        if (!isset($data['device_id'])) return;
        $this->userRepository->upsertDevice($user, [
            'device_id' => $data['device_id'],
            'token' => $data['device_token'] ?? null,
            'device_type' => $data['device_type'] ?? null,
        ]);
    }

    /**
     * Xác minh token Google — throw nếu sai.
     * @param string $idToken
     * @return array
     * @throws ServiceException
     */
    private function verifyGoogleToken(string $idToken): array
    {
        $tks = explode('.', $idToken);
        if (count($tks) !== 3) {
            $this->throw('Wrong number of segments in token', 400);
        }
        $header = JWT::jsonDecode(JWT::urlsafeB64Decode($tks[0]));
        if (empty($header->kid)) {
            $this->throw('kid not found in token header', 400);
        }

        $publicKeys = $this->fetchGooglePublicKeys();
        if (!isset($publicKeys[$header->kid])) {
            // refetch keys and try again
            $publicKeys = $this->fetchGooglePublicKeys(true);
            if (!isset($publicKeys[$header->kid])) {
                $this->throw('kid not found in google certs', 400);
            }
        }

        JWT::$leeway = 60; // 60 seconds
        $publicKey = $publicKeys[$header->kid];
        $decoded = JWT::decode($idToken, $publicKeys[$header->kid]);

        // validate audience - support multiple client IDs separated by comma
        $audiences = explode(',', config('services.google.client_id'));
        $audiences = array_map('trim', $audiences);

        if (!in_array($decoded->aud, $audiences)) {
            $this->throw('Đối tượng không hợp lệ' . $decoded->aud, 400);
        }

        // validate issuer
        if (!in_array($decoded->iss, ['https://accounts.google.com', 'accounts.google.com'])) {
            $this->throw('Tổ chức phát hành không hợp lệ' . $decoded->iss, 400);
        }

        return (array)$decoded;
    }

    /**
     * Lấy public key Google.
     * @return array
     * @throws ServiceException
     */
    private function fetchGooglePublicKeys(bool $forceRefresh = false): array
    {
        $cacheKey = 'google_public_keys';

        if (!$forceRefresh && \Cache::has($cacheKey)) {
            return \Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(5)
                ->get('https://www.googleapis.com/oauth2/v3/certs');

            if ($response->failed()) {
                $this->throw('Không thể kết nối tới Google API để lấy public keys.', 500);
            }

            $jwks = $response->json();

            if (empty($jwks) || !isset($jwks['keys'])) {
                $this->throw('Dữ liệu public key từ Google không hợp lệ.', 500);
            }

            $publicKeys = JWK::parseKeySet($jwks);

            \Cache::put($cacheKey, $publicKeys, 3600);

            return $publicKeys;

        } catch (\Exception $e) {
            \Log::error("Google Certs Fetch Error: " . $e->getMessage(), [
                'exception' => $e
            ]);

            $this->throw('Could not fetch Google public keys: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Xác minh token Apple — throw nếu sai.
     * @param string $idToken
     * @param array $config
     * @return array
     * @throws ServiceException
     */
    private function verifyAppleToken(string $idToken, array $config): array
    {
        $tks = explode('.', $idToken);
        if (count($tks) !== 3) {
            $this->throw('Wrong number of segments in token', 400);
        }
        $header = JWT::jsonDecode(JWT::urlsafeB64Decode($tks[0]));
        if (empty($header->kid)) {
            $this->throw('kid not found in token header', 400);
        }

        $jwks = $this->fetchApplePublicKeys();

        try {
            $publicKeys = JWK::parseKeySet($jwks);
        } catch (\Exception $e) {
            $this->throw('Invalid Apple public keys format.', 500);
        }

        if (!isset($publicKeys[$header->kid])) {
            \Cache::forget('apple_public_keys');
            $jwks = $this->fetchApplePublicKeys();
            try {
                $publicKeys = JWK::parseKeySet($jwks);
            } catch (\Exception $e) {
                $this->throw('Invalid Apple public keys format.', 500);
            }

            if (!isset($publicKeys[$header->kid])) {
                $this->throw('kid not found in apple certs', 400);
            }
        }

        JWT::$leeway = 60;
        $publicKey = $publicKeys[$header->kid];
        $decoded = JWT::decode($idToken, $publicKey);

        if (empty($decoded->sub)) {
            $this->throw('Invalid Apple token: missing sub.', 400);
        }

        if ($decoded->iss !== 'https://appleid.apple.com') {
            $this->throw('Invalid issuer', 400);
        }

        // validate audience - support multiple client IDs separated by comma
        $audiences = explode(',', $config['client_id']);
        $audiences = array_map('trim', $audiences);

        if (!in_array($decoded->aud, $audiences)) {
            $this->throw('Invalid audience', 400);
        }

        return (array) $decoded;
    }

    /**
     * Lấy public keys của Apple.
     * @return array
     * @throws ServiceException
     */
    private function fetchApplePublicKeys(): array
    {
        $cacheKey = 'apple_public_keys';
        if (\Cache::has($cacheKey)) {
            return \Cache::get($cacheKey);
        }

        $response = file_get_contents('https://appleid.apple.com/auth/keys');
        $jwks = json_decode($response, true);

        if (empty($jwks) || !isset($jwks['keys'])) {
            $this->throw('Could not fetch Apple public keys.', 500);
        }

        \Cache::put($cacheKey, $jwks, 3600);

        return $jwks;
    }

    /**
     * Trích tên từ claims Apple — return null nếu không có.
     * @param array $nameData
     * @return string|null
     */
    private function extractAppleName(array $nameData): ?string
    {
        $firstName = $nameData['firstName'] ?? '';
        $lastName = $nameData['lastName'] ?? '';
        return trim($firstName . ' ' . $lastName) ?: null;
    }

}
