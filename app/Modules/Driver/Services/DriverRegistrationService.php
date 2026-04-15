<?php

declare(strict_types=1);

namespace App\Modules\Driver\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Driver\DTO\RegisterDriverSubmitDTO;
use App\Modules\Driver\Events\DriverApplicationSubmitted;
use App\Modules\Driver\Interfaces\DriverRegistrationRepositoryInterface;
use App\Modules\Driver\Interfaces\DriverRegistrationServiceInterface;
use App\Modules\Driver\Interfaces\FileRecordRepositoryInterface;
use App\Modules\Driver\Model\Enums\FileableType;
use App\Modules\Driver\Model\Enums\FileDisk;
use App\Modules\Driver\Model\Enums\KycType;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class DriverRegistrationService extends BaseService implements DriverRegistrationServiceInterface
{
    private const STORAGE_PATH = 'driver-kyc'; // local disk — KYC docs không public

    public function __construct(
        private readonly DriverRegistrationRepositoryInterface $driverRegistrationRepository,
        private readonly FileRecordRepositoryInterface         $fileRecordRepository,
        private readonly UserRepositoryInterface               $userRepository,
    ) {}

    /**
     * UC-30 Nộp tài liệu → tạo hồ sơ Pending.
     * Alternative Flows: A3, A4, A8, A13.
     */
    public function submitRegistration(RegisterDriverSubmitDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {

            // 1. Kiểm tra user tồn tại và đang hoạt động
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy tài khoản.', 404);
            $this->validate($user->isActive(), 'Tài khoản của bạn đã bị vô hiệu hóa.', 403);

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

            // 4. Tạo hồ sơ Pending (UC-30)
            $application = $this->driverRegistrationRepository->createDriverApplication(
                userId:       $dto->userId,
                snapshotData: $snapshotData,
                kycType:      KycType::DRIVER,
            );

            // 5. Upload + lưu metadata tài liệu (UC-30)
            $this->storeAllDocuments($application->id, $dto->files);

            // 6. Raise Domain Event — Admin nhận thông báo hồ sơ mới
            event(new DriverApplicationSubmitted($application->id, $dto->userId));

            // 7. Response
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

    /**
     * Upload toàn bộ 8 tài liệu và lưu metadata qua FileRecordRepository.
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
