<?php

declare(strict_types=1);

namespace App\Modules\Driver\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthOtpRepositoryInterface;
use App\Modules\Driver\DTO\RegisterDriverInitiateDTO;
use App\Modules\Driver\DTO\RegisterDriverSubmitDTO;
use App\Modules\Driver\Events\DriverApplicationSubmitted;
use App\Modules\Driver\Interfaces\DriverRegistrationRepositoryInterface;
use App\Modules\Driver\Interfaces\DriverRegistrationServiceInterface;
use App\Modules\Driver\Interfaces\FileRecordRepositoryInterface;
use App\Modules\Driver\Model\Enums\FileableType;
use App\Modules\Driver\Model\Enums\FileDisk;
use App\Modules\Driver\Model\Enums\KycType;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\UserOtpType;
use App\Modules\User\Model\UserOtp;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class DriverRegistrationService extends BaseService implements DriverRegistrationServiceInterface
{
    private const RETRY_AFTER_SECONDS = 180;
    private const MAX_SEND_PER_DAY   = 5;
    private const MAX_OTP_ATTEMPTS   = 5;
    private const STORAGE_PATH       = 'driver-kyc'; // local disk — KYC docs không public

    public function __construct(
        private readonly DriverRegistrationRepositoryInterface $driverRegistrationRepository,
        private readonly FileRecordRepositoryInterface         $fileRecordRepository,
        private readonly AuthOtpRepositoryInterface            $authOtpRepository,
        private readonly UserRepositoryInterface               $userRepository,
    ) {}

    // =========================================================================
    // UC-30 Bước 1: Validate thông tin + gửi OTP (Normal Flow 1–13)
    // =========================================================================

    public function initiateRegistration(RegisterDriverInitiateDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {

            // 1. Kiểm tra user tồn tại
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy tài khoản.', 404);

            // 2. UC-30 A9 — đã là tài xế (role = Driver)
            $this->validate(
                !$user->isDriver(),
                'Bạn đã là tài xế. Không thể đăng ký thêm.',
                409
            );

            // 3. Kiểm tra hồ sơ đang chờ duyệt (Pending/Approved)
            $this->validate(
                $this->driverRegistrationRepository->findActiveApplicationByUser(
                    $dto->userId, KycType::DRIVER
                ) === null,
                'Bạn đã có hồ sơ đang chờ xét duyệt. Vui lòng chờ kết quả.',
                409
            );

            // 4. UC-30 A6 — CCCD không được trùng
            $this->validate(
                !$this->driverRegistrationRepository->existsByCitizenId($dto->citizenId, $dto->userId),
                'CCCD đã được sử dụng bởi tài khoản khác.',
                422
            );

            // 5. UC-30 A7 — Biển số không được trùng
            $this->validate(
                !$this->driverRegistrationRepository->existsByVehicleNumber($dto->vehicleNumber, $dto->userId),
                'Phương tiện đã được đăng ký bởi tài khoản khác.',
                422
            );

            // 6. Gửi OTP (UC-30 bước 12–13)
            $otpRecord = $this->dispatchOtp($dto->phone);

            $response = [
                'retry_after_seconds' => self::RETRY_AFTER_SECONDS,
                'expires_at'          => $otpRecord->expired_at,
                'message'             => 'Mã OTP đã được gửi đến số ' . $dto->phone,
            ];

            // Dev mode chỉ — expose OTP để test (không chạy ở production)
            if (config('services.otp_expose') === true && !app()->isProduction()) {
                $response['otp_code'] = $otpRecord->plain_code;
            }

            return $response;
        });
    }

    // =========================================================================
    // UC-30 Bước 2: Xác thực OTP + upload file + tạo hồ sơ (Normal Flow 14–19)
    // =========================================================================

    public function submitRegistration(RegisterDriverSubmitDTO $dto): ServiceReturn
    {
        // Xác thực OTP NGOÀI transaction — để attempts không bị rollback (UC-30 A10, A11)
        $otpResult = $this->verifyOtp($dto->phone, $dto->otp);
        if ($otpResult->isError()) {
            return $otpResult;
        }

        return $this->execute(function () use ($dto) {

            // 1. Kiểm tra lại user
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy tài khoản.', 404);

            // 2. Double-check business rules trong transaction (atomic)
            $this->validate(!$user->isDriver(), 'Bạn đã là tài xế.', 409);

            $this->validate(
                $this->driverRegistrationRepository->findActiveApplicationByUser(
                    $dto->userId, KycType::DRIVER
                ) === null,
                'Bạn đã có hồ sơ đang chờ xét duyệt.',
                409
            );

            $this->validate(
                !$this->driverRegistrationRepository->existsByCitizenId($dto->citizenId, $dto->userId),
                'CCCD đã được sử dụng bởi tài khoản khác.',
                422
            );

            $this->validate(
                !$this->driverRegistrationRepository->existsByVehicleNumber($dto->vehicleNumber, $dto->userId),
                'Phương tiện đã được đăng ký bởi tài khoản khác.',
                422
            );

            // 3. Tạo snapshot — đóng băng dữ liệu tại thời điểm nộp
            $snapshotData = [
                'full_name'      => $dto->fullName,
                'phone'          => $dto->phone,
                'citizen_id'     => $dto->citizenId,
                'vehicle_type'   => $dto->vehicleType->value,
                'vehicle_name'   => $dto->vehicleName,
                'vehicle_color'  => $dto->vehicleColor->value,
                'vehicle_number' => $dto->vehicleNumber,
                'vehicle_year'   => $dto->vehicleYear,
                'submitted_at'   => now()->toISOString(),
            ];

            // 4. Tạo hồ sơ Pending (UC-30 bước 17–18)
            $application = $this->driverRegistrationRepository->createDriverApplication(
                userId:       $dto->userId,
                snapshotData: $snapshotData,
                kycType:      KycType::DRIVER,
            );

            // 5. Upload + lưu metadata tài liệu (UC-30 bước 7, A3, A4, A13)
            $this->storeAllDocuments($application->id, $dto->files);

            // 6. Đánh dấu OTP đã dùng — tránh replay attack
            $this->authOtpRepository->markLatestAsUsed($dto->phone, UserOtpType::VERIFY_DRIVER_REGISTER);

            // 7. Raise Domain Event — Admin nhận thông báo hồ sơ mới
            event(new DriverApplicationSubmitted($application->id, $dto->userId));

            // 8. Response (UC-30 bước 19)
            return $this->success(
                data: [
                    'application_id' => $application->id,
                    'kyc_status'     => $application->kyc_status->getLabel(),
                    'submitted_at'   => $snapshotData['submitted_at'],
                ],
                message: 'Đăng ký thành công. Vui lòng chờ xét duyệt.'
            );

        }, useTransaction: true);
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Gửi OTP — throttle 3 phút/lần, tối đa 5 lần/ngày.
     * UC-30 bước 12–13, A12.
     */
    private function dispatchOtp(string $phone): UserOtp
    {
        $lastOtp = $this->authOtpRepository->getLastOtp($phone, UserOtpType::VERIFY_DRIVER_REGISTER);

        if ($lastOtp && $lastOtp->created_at->addSeconds(self::RETRY_AFTER_SECONDS)->isFuture()) {
            $left = now()->diffInSeconds($lastOtp->created_at->addSeconds(self::RETRY_AFTER_SECONDS));
            $this->throw("Vui lòng đợi {$left} giây trước khi yêu cầu mã OTP mới.", 429);
        }

        $sentToday = $this->authOtpRepository->countSentToday($phone, UserOtpType::VERIFY_DRIVER_REGISTER);
        if ($sentToday >= self::MAX_SEND_PER_DAY) {
            $this->throw('Quá số lần gửi OTP trong ngày. Vui lòng thử lại vào ngày mai.', 429);
        }

        return $this->authOtpRepository->generateOtp($phone, UserOtpType::VERIFY_DRIVER_REGISTER);
    }

    /**
     * Xác thực OTP — chạy NGOÀI transaction để giữ attempts count.
     * UC-30 A10 (sai), A11 (hết hạn).
     */
    private function verifyOtp(string $phone, string $code): ServiceReturn
    {
        return $this->execute(function () use ($phone, $code) {

            $otpRecord = $this->authOtpRepository->getLastOtp($phone, UserOtpType::VERIFY_DRIVER_REGISTER);

            if (!$otpRecord) {
                $this->throw('Mã OTP không tồn tại. Vui lòng yêu cầu gửi lại.', 400);
            }

            if ($otpRecord->used_at) {
                $this->throw('Mã OTP đã được sử dụng.', 400);
            }

            // UC-30 A11 — hết hạn
            if ($otpRecord->isExpired()) {
                $this->throw('Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.', 400);
            }

            if ($otpRecord->attempts >= self::MAX_OTP_ATTEMPTS) {
                $this->throw('Mã OTP đã bị khóa do nhập sai quá nhiều. Vui lòng yêu cầu mã mới.', 400);
            }

            // UC-30 A10 — sai OTP
            if (!$otpRecord->checkCode($code)) {
                $this->authOtpRepository->incrementAttempts($otpRecord);
                $remaining = self::MAX_OTP_ATTEMPTS - ($otpRecord->attempts + 1);
                $this->throw("Mã OTP không chính xác. Còn {$remaining} lần thử.", 400);
            }

            $this->authOtpRepository->markAsVerified($otpRecord);
            return true;
        });
    }

    /**
     * Upload toàn bộ 8 tài liệu và lưu metadata qua FileRecordRepository.
     * UC-30 bước 7, A3, A4, A13.
     *
     * @param array<string, UploadedFile|null> $files
     */
    private function storeAllDocuments(int $applicationId, array $files): void
    {
        $typeMap = [
            'cccd_front'      => FileableType::DRIVER_REVIEW_CCCD_FRONT,
            'cccd_back'       => FileableType::DRIVER_REVIEW_CCCD_BACK,
            'driver_license'  => FileableType::DRIVER_REVIEW_LICENSE,
            'vehicle_reg'     => FileableType::DRIVER_REVIEW_VEHICLE_REG,
            'criminal_record' => FileableType::DRIVER_REVIEW_CRIMINAL_RECORD,
            'health_cert'     => FileableType::DRIVER_REVIEW_HEALTH_CERT,
            'portrait'        => FileableType::DRIVER_REVIEW_PORTRAIT,
            'insurance'       => FileableType::DRIVER_REVIEW_INSURANCE,
        ];

        foreach ($typeMap as $key => $fileableType) {
            /** @var UploadedFile|null $file */
            $file = $files[$key] ?? null;

            if (!$file instanceof UploadedFile) {
                continue;
            }

            $storedPath = $this->uploadToStorage($file, $applicationId);

            // Lưu metadata qua FileRecordRepository — không gọi Model tĩnh
            $this->fileRecordRepository->storeFile(
                fileableId:   $applicationId,
                fileableType: $fileableType,
                name:         Str::uuid() . '.' . $file->extension(),
                realName:     $file->getClientOriginalName(),
                path:         $storedPath,
                disk:         FileDisk::PRIVATE,
                size:         (int) $file->getSize(),
                mimeType:     $file->getMimeType() ?? $file->getClientMimeType(),
            );
        }
    }

    /**
     * Upload file lên local disk.
     * UC-30 A13 — throw InfrastructureException nếu thất bại.
     */
    private function uploadToStorage(UploadedFile $file, int $applicationId): string
    {
        $folder   = self::STORAGE_PATH . '/' . $applicationId;
        $fileName = Str::uuid() . '.' . $file->extension();
        $path     = $file->storeAs($folder, $fileName, 'local');

        if ($path === false) {
            $this->throw('Tải file thất bại. Vui lòng thử lại.', 500);
        }

        return $path;
    }
}
