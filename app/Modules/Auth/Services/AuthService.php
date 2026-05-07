<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceException;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\DTO\AppleLoginDTO;
use App\Modules\Auth\DTO\ForgotPasswordDTO;
use App\Modules\Auth\DTO\GoogleLoginDTO;
use App\Modules\Auth\DTO\LoginDTO;
use App\Modules\Auth\DTO\RegisterDTO;
use App\Modules\Auth\DTO\SendOtpDTO;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

final class AuthService extends BaseService implements AuthServiceInterface
{
    protected const RETRY_AFTER_SECONDS = 180; // 3 phút
    protected const MAX_SEND_PER_DAY    = 5;
    protected const MAX_OTP_ATTEMPTS    = 5;

    public function __construct(
        private readonly UserRepositoryInterface    $userRepository,
        private readonly AuthOtpRepositoryInterface $authOtpRepository,
    ) {
    }

    /**
     * POST /authenticate-otp
     * Validate context (đăng ký vs đăng nhập) → throttle → dispatch OTP.
     */
    public function sendOtp(SendOtpDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $this->assertPhoneEligibleForOtp($dto->phone, $dto->type);

            $otpRecord = $this->dispatchOtp($dto->phone, $dto->type);

            $response = [
                'retry_after_seconds' => self::RETRY_AFTER_SECONDS,
                'expires_at'          => $otpRecord->expired_at,
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
    public function register(RegisterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $otpType = $dto->role === UserRole::Driver ? UserOtpType::VERIFY_DRIVER_REGISTER : UserOtpType::VERIFY_REGISTER;
            $this->verifyOtpOrFail($dto->phone, $dto->otp, $otpType);

            if ($this->userRepository->existsByPhone($dto->phone)) {
                $this->throw('Số điện thoại đã được đăng ký.', 409);
            }

            $user = $this->userRepository->create([
                'phone'             => $dto->phone,
                'password'          => bcrypt($dto->password),
                'role'              => $dto->role,
                'is_verified'       => true,
                'is_phone_verified' => true,
                'is_active'         => true,
            ]);

            // Mọi user mới đều khởi tạo CustomerProfile cơ bản (họ tên, giới tính)
            $this->userRepository->createCustomerProfile($user, [
                'full_name' => $dto->fullName,
                'gender'    => Gender::Other->value,
            ]);

            $this->upsertDeviceIfPresent($user, $dto->deviceId, $dto->deviceToken, $dto->deviceType);
            $this->authOtpRepository->markLatestAsUsed($dto->phone, $otpType);
            $token = $this->generateTokenAuth($user);

            // Tải profile tương ứng với role (nếu là Driver thì chưa có driverProfile, chỉ có customerProfile vừa tạo)
            $user->load($this->getProfileRelation($user->role));

            return [
                'user'  => $user,
                'token' => $token,
            ];
        });
    }

    /**
     * POST /login
     * Kiểm tra thông tin đăng nhập (SĐT + Mật khẩu) → kiểm tra trạng thái → trả token.
     */
    public function login(LoginDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->userRepository->findByPhone($dto->phone);

            $this->validate($user !== null, 'Số điện thoại chưa được đăng ký.', 404);

            if (!Hash::check($dto->password, $user->password)) {
                $this->throw('Mật khẩu không chính xác.', 401);
            }

            $this->validate(!$user->isLocked(), 'Tài khoản này đã bị khóa. Vui lòng liên hệ hỗ trợ.', 403);

            $this->upsertDeviceIfPresent($user, $dto->deviceId, $dto->deviceToken, $dto->deviceType);
            $token = $this->generateTokenAuth($user);
            $user->load($this->getProfileRelation($user->role));

            return [
                'user'  => $user,
                'token' => $token,
            ];
        }, useTransaction: true);
    }

    /**
     * POST /forgot-password (reset-password)
     * Xác thực OTP → đặt lại mật khẩu → trả token.
     */
    public function forgotPassword(ForgotPasswordDTO $dto): ServiceReturn
    {
        // Kiểm tra user tồn tại ngoài transaction
        $user = $this->userRepository->findByPhone($dto->phone);

        if (!$user) {
            $this->throw('Số điện thoại này chưa được đăng ký trên hệ thống.', 404);
        }

        if ($user->isLocked()) {
            $this->throw('Tài khoản này đã bị khóa. Vui lòng liên hệ hỗ trợ.', 403);
        }

        if (Hash::check($dto->password, $user->password)) {
            // Dùng $this->throw() thay vì ServiceReturn::error() — theo quy tắc kiến trúc
            $this->throw('Mật khẩu mới không được trùng mật khẩu cũ.', 422);
        }

        // Xác thực OTP ngoài transaction để record attempts không bị rollback
        $this->verifyOtpOrFail($dto->phone, $dto->otp, UserOtpType::VERIFY_FORGOT_PASSWORD);

        return $this->execute(function () use ($dto, $user) {
            $this->userRepository->updateById($user->id, [
                'password' => bcrypt($dto->password),
            ]);

            $this->authOtpRepository->markLatestAsUsed($dto->phone, UserOtpType::VERIFY_FORGOT_PASSWORD);

            $this->upsertDeviceIfPresent($user, $dto->deviceId, $dto->deviceToken, $dto->deviceType);
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
     */
    public function googleLogin(GoogleLoginDTO $dto): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($dto) {
                $claims   = $this->verifyGoogleToken($dto->idToken);
                $googleId = $claims['sub'];

                $user = $this->userRepository->findByGoogleId($googleId);

                if (!$user) {
                    $email = $claims['email'] ?? null;
                    $name  = $claims['name'] ?? null;

                    if (!$name) {
                        $name = $email ? explode('@', $email)[0] : 'User ' . $googleId;
                    }

                    if ($email && $this->userRepository->findByEmail($email)) {
                        $this->throw('Địa chỉ email đã được sử dụng bởi một tài khoản khác.', 409);
                    }

                    $user = $this->userRepository->create([
                        'google_id'   => $googleId,
                        'email'       => $email,
                        'password'    => bcrypt(\Str::random(16)),
                        'role'        => UserRole::Customer,
                        'is_verified' => true,
                        'is_active'   => true,
                    ]);
                }

                if (!$user->customerProfile) {
                    $this->userRepository->createCustomerProfile($user, [
                        'full_name' => $name ?? 'User',
                        'gender'    => Gender::Other->value,
                    ]);
                }

                $this->validate(!$user->isLocked(), 'Tài khoản này đã bị khóa. Vui lòng liên hệ hỗ trợ.', 403);

                $this->upsertDeviceIfPresent($user, $dto->deviceId, $dto->deviceToken, $dto->deviceType);
                $token = $this->generateTokenAuth($user);
                $user->load($this->getProfileRelation($user->role));

                return ['user' => $user, 'token' => $token];
            },
            useTransaction: true
        );
    }

    /**
     * POST /apple-login
     */
    public function appleLogin(AppleLoginDTO $dto): ServiceReturn
    {
        return $this->execute(
            callback: function () use ($dto) {
                $appleConfig = config('services.apple');
                $claims      = $this->verifyAppleToken($dto->idToken, $appleConfig);
                $appleId     = $claims['sub'];

                $user = $this->userRepository->findByAppleId($appleId);

                if (!$user) {
                    $email = $claims['email'] ?? null;
                    $name  = $this->extractAppleName($dto->name ?? []);

                    if (!$name) {
                        $name = $email ? explode('@', $email)[0] : 'User ' . $appleId;
                    }

                    if ($email && $this->userRepository->findByEmail($email)) {
                        $this->throw('Địa chỉ email đã được sử dụng bởi một tài khoản khác.', 409);
                    }

                    $user = $this->userRepository->create([
                        'apple_id'    => $appleId,
                        'email'       => $email,
                        'password'    => bcrypt(\Str::random(16)),
                        'role'        => UserRole::Customer,
                        'is_verified' => true,
                        'is_active'   => true,
                    ]);
                }

                if (!$user->customerProfile) {
                    $this->userRepository->createCustomerProfile($user, [
                        'full_name' => $name ?? 'User',
                        'gender'    => Gender::Other->value,
                    ]);
                }

                $this->validate(!$user->isLocked(), 'Tài khoản này đã bị khóa. Vui lòng liên hệ hỗ trợ.', 403);

                $this->upsertDeviceIfPresent($user, $dto->deviceId, $dto->deviceToken, $dto->deviceType);
                $token = $this->generateTokenAuth($user);
                $user->load($this->getProfileRelation($user->role));

                return ['user' => $user, 'token' => $token];
            },
            useTransaction: true
        );
    }

    /**
     * POST /verify-otp
     * Chỉ xác thực OTP mà không thực hiện hành động gì khác.
     */
    public function verifyOtp(string $phone, string $otp, UserOtpType $type): ServiceReturn
    {
        return $this->execute(function () use ($phone, $otp, $type) {
            $this->verifyOtpOrFail($phone, $otp, $type);
            return true;
        });
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

    // =================== PRIVATE HELPERS ===================

    /**
     * Lấy relation profile theo role.
     */
    private function getProfileRelation(UserRole $role): string
    {
        return match ($role) {
            UserRole::Customer  => 'customerProfile',
            UserRole::Merchants => 'merchantProfile',
            UserRole::Admin     => 'customerProfile', // Admin dùng chung customerProfile để lấy thông tin cơ bản
            UserRole::Driver    => 'driverProfile',
        };
    }


    /**
     * Gửi OTP đến số điện thoại.
     */
    private function dispatchOtp(string $phone, UserOtpType $type): UserOtp
    {
        $lastOtp = $this->authOtpRepository->getLastOtp($phone, $type);

        if ($lastOtp && $lastOtp->created_at->addSeconds(self::RETRY_AFTER_SECONDS)->isFuture()) {
            $retryAfter = now()->diffInSeconds($lastOtp->created_at->addSeconds(self::RETRY_AFTER_SECONDS));
            $this->throw("Vui lòng đợi {$retryAfter} giây trước khi yêu cầu mã mới.", 429);
        }

        $sentToday = $this->authOtpRepository->countSentToday($phone, $type);
        if ($sentToday >= self::MAX_SEND_PER_DAY) {
            $this->throw('Quá số lần gửi OTP trong ngày.', 429);
        }

        return $this->authOtpRepository->generateOtp($phone, $type);
    }

    /**
     * Xác minh OTP — throw nếu sai / hết hạn / đã dùng.
     */
    private function verifyOtpOrFail(string $phone, string $code, UserOtpType $type): void
    {
        $otpRecord = $this->authOtpRepository->getLastOtp($phone, $type);

        if (!$otpRecord) {
            $this->throw('Mã OTP không tồn tại.', 400);
        }

        if ($otpRecord->isUsed()) {
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
     */
    private function assertPhoneEligibleForOtp(string $phone, UserOtpType $type): void
    {
        $exists = $this->userRepository->existsByPhone($phone);

        match ($type) {
            UserOtpType::VERIFY_REGISTER,
            UserOtpType::VERIFY_DRIVER_REGISTER => $exists ? $this->throw('Số điện thoại đã đăng ký.', 409) : null,
            UserOtpType::VERIFY_LOGIN,
            UserOtpType::VERIFY_FORGOT_PASSWORD,
            UserOtpType::VERIFY_MERCHANT_REGISTER => !$exists ? $this->throw('Số điện thoại chưa đăng ký.', 404) : null,
            UserOtpType::CHANGE_PROFILE => null, // Cho phép cả số chưa và đã đăng ký
            default => $this->throw('Loại OTP không hợp lệ.', 400),
        };
    }

    /**
     * Tạo token auth cho user.
     */
    private function generateTokenAuth(User $user): string
    {
        return $user->createToken('auth_token', ['*'], now()->addMonth())->plainTextToken;
    }

    /**
     * Cập nhật device cho user nếu có.
     */
    private function upsertDeviceIfPresent(User $user, ?string $deviceId, ?string $deviceToken, ?string $deviceType): void
    {
        if (!$deviceId) {
            return;
        }

        $this->userRepository->upsertDevice($user, [
            'device_id'   => $deviceId,
            'token'       => $deviceToken,
            'device_type' => $deviceType,
        ]);
    }

    /**
     * Xác minh token Google — throw nếu sai.
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
            $publicKeys = $this->fetchGooglePublicKeys(forceRefresh: true);
            if (!isset($publicKeys[$header->kid])) {
                $this->throw('kid not found in google certs', 400);
            }
        }

        JWT::$leeway = 60;
        $decoded     = JWT::decode($idToken, $publicKeys[$header->kid]);

        $audiences = array_map('trim', explode(',', config('services.google.client_id')));
        if (!in_array($decoded->aud, $audiences, strict: true)) {
            $this->throw('Đối tượng không hợp lệ: ' . $decoded->aud, 400);
        }

        if (!in_array($decoded->iss, ['https://accounts.google.com', 'accounts.google.com'], strict: true)) {
            $this->throw('Tổ chức phát hành không hợp lệ: ' . $decoded->iss, 400);
        }

        return (array) $decoded;
    }

    /**
     * Lấy public key Google.
     */
    private function fetchGooglePublicKeys(bool $forceRefresh = false): array
    {
        $cacheKey = 'google_public_keys';

        if (!$forceRefresh && \Cache::has($cacheKey)) {
            return \Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(5)->get('https://www.googleapis.com/oauth2/v3/certs');

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
            \Log::error('Google Certs Fetch Error: ' . $e->getMessage(), ['exception' => $e]);
            $this->throw('Could not fetch Google public keys: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Xác minh token Apple — throw nếu sai.
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
        $decoded     = JWT::decode($idToken, $publicKeys[$header->kid]);

        if (empty($decoded->sub)) {
            $this->throw('Invalid Apple token: missing sub.', 400);
        }

        if ($decoded->iss !== 'https://appleid.apple.com') {
            $this->throw('Invalid issuer', 400);
        }

        $audiences = array_map('trim', explode(',', $config['client_id']));
        if (!in_array($decoded->aud, $audiences, strict: true)) {
            $this->throw('Invalid audience', 400);
        }

        return (array) $decoded;
    }

    /**
     * Lấy public keys của Apple.
     */
    private function fetchApplePublicKeys(): array
    {
        $cacheKey = 'apple_public_keys';

        if (\Cache::has($cacheKey)) {
            return \Cache::get($cacheKey);
        }

        $response = file_get_contents('https://appleid.apple.com/auth/keys');
        $jwks     = json_decode($response, true);

        if (empty($jwks) || !isset($jwks['keys'])) {
            $this->throw('Could not fetch Apple public keys.', 500);
        }

        \Cache::put($cacheKey, $jwks, 3600);

        return $jwks;
    }

    /**
     * Trích tên từ claims Apple.
     */
    private function extractAppleName(array $nameData): ?string
    {
        $firstName = $nameData['firstName'] ?? '';
        $lastName  = $nameData['lastName']  ?? '';

        return trim($firstName . ' ' . $lastName) ?: null;
    }
}
