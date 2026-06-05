<?php

declare(strict_types=1);

namespace App\Modules\Driver\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Driver\DTO\ApproveRegistrationDTO;
use App\Modules\Driver\DTO\RegisterDriverSubmitDTO;
use App\Modules\Driver\Events\DriverApplicationApproved;
use App\Modules\Driver\Events\DriverApplicationSubmitted;
use App\Modules\Driver\Interfaces\DriverRegistrationRepositoryInterface;
use App\Modules\Driver\Interfaces\DriverRegistrationServiceInterface;
use App\Modules\Driver\Interfaces\FileRecordRepositoryInterface;
use App\Modules\Driver\Model\Enums\FileableType;
use App\Modules\Driver\Model\Enums\FileDisk;
use App\Modules\Driver\Model\Enums\KycStatus;
use App\Modules\Driver\Model\Enums\KycType;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Interfaces\DriverGroupRepositoryInterface;
use App\Modules\User\Model\Enums\DriverGroupType;
use App\Modules\User\Model\Enums\DriverStatus;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\Enums\VehicleType;
use App\Modules\User\Model\Enums\VehicleColor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class DriverRegistrationService extends BaseService implements DriverRegistrationServiceInterface
{
    private const STORAGE_PATH = 'driver-kyc'; // local disk — KYC docs không public

    public function __construct(
        private readonly DriverRegistrationRepositoryInterface $driverRegistrationRepository,
        private readonly FileRecordRepositoryInterface         $fileRecordRepository,
        private readonly UserRepositoryInterface               $userRepository,
        private readonly DriverProfileRepositoryInterface       $driverProfileRepository,
        private readonly DriverGroupRepositoryInterface         $driverGroupRepository,
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

            // 2. Kiểm tra kỹ các quy tắc nghiệp vụ trong giao dịch (nguyên tử)
            $this->validate($user->driverProfile === null, 'Tài khoản của bạn đã là tài xế chính thức.', 409);

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
                'services'       => $dto->services,
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
            event(new DriverApplicationSubmitted((string)$application->id, $dto->userId));

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
     * Admin duyệt hồ sơ tài xế.
     * Quy trình: Approve KYC -> Upgrade User Role -> Create Driver Profile.
     */
    public function approveRegistration(ApproveRegistrationDTO $dto): ServiceReturn
    {
        return $this->execute(callback: function () use ($dto) {
            // 1. Tìm hồ sơ
            $application = $this->driverRegistrationRepository->findById($dto->applicationId);
            $this->validate($application !== null, 'Không tìm thấy hồ sơ đăng ký.', 404);
            $this->validate($application->kyc_status->isPending(), 'Hồ sơ này không ở trạng thái chờ duyệt.', 400);

            $userId       = $application->user_id;
            $snapshotData = $application->snapshot_data;

            // 2. Chạy giao dịch các bảng liên quan
            // - Cập nhật trạng thái hồ sơ
            $this->driverRegistrationRepository->updateStatus($application->id, KycStatus::APPROVED);

            // - Nâng cấp vai trò user sang Driver
            $this->userRepository->updateRole($userId, UserRole::Driver);

            // - Tạo hồ sơ vận hành (DriverProfile)
            // Xác định loại đội xe (Xe nhà vs Đối tác)
            $driverGroupType = $dto->driverGroupId ? DriverGroupType::INTERNAL : DriverGroupType::PARTNER;

            // - Lấy danh sách file đã upload để đồng bộ sang DriverProfile
            $files = $this->fileRecordRepository->findByApplicationId($application->id);
            $fileMap = $files->mapWithKeys(fn($file) => [$file->fileable_type->value => $file->path])->toArray();

            $this->driverProfileRepository->create([
                'user_id'           => $userId,
                'full_name'         => $snapshotData['full_name'] ?? 'Driver ' . $userId,
                'driver_group_id'   => $dto->driverGroupId,
                'driver_group_type' => $driverGroupType->value,
                'vehicle_type'      => VehicleType::tryFrom((int)($snapshotData['vehicle_type'] ?? 1))?->value ?? VehicleType::BIKE->value,
                'vehicle_name'      => $snapshotData['vehicle_name'] ?? 'N/A',
                'vehicle_color'     => VehicleColor::tryFrom((int)($snapshotData['vehicle_color'] ?? 0))?->value ?? VehicleColor::Unknown->value,
                'vehicle_number'    => $snapshotData['vehicle_number'] ?? 'N/A',
                'is_online'         => false,
                'status'            => DriverStatus::ACTIVE->value, // Sẵn sàng hoạt động
                // Đồng bộ ảnh KYC
                'license_number'      => $snapshotData['license_number'] ?? ($snapshotData['citizen_id'] ?? null),
                'license_front_image' => $fileMap[FileableType::DRIVER_REVIEW_LICENSE->value] ?? null,
                'license_back_image'  => $fileMap[FileableType::DRIVER_REVIEW_CCCD_FRONT->value] ?? null,
            ]);

            // - Raise Domain Event — Thông báo realtime cho frontend
            event(new DriverApplicationApproved($application->id, $userId));

            return [
                'user_id'        => $userId,
                'application_id' => $application->id,
                'status'         => 'Đã phê duyệt thành công',
            ];
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

    /**
     * @inheritDoc
     */
    public function getApplications(): ServiceReturn
    {
        return $this->execute(function () {
            return $this->driverRegistrationRepository->getPendingApplications();
        });
    }

    /**
     * @inheritDoc
     */
    public function getApplicationDetails(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $application = $this->driverRegistrationRepository->findByIdWithUser($id);
            $this->validate($application !== null, 'Không tìm thấy hồ sơ.', 404);

            $files = $this->fileRecordRepository->findByApplicationId((int) $id);

            // Gộp các link ảnh vào snapshot_data để UI dễ hiển thị
            $snapshotData = $application->snapshot_data;
            foreach ($files as $file) {
                // Ví dụ: cccd_front -> cccd_front_url
                $key = $file->fileable_type->getRegisterKey(); 
                if ($key) {
                    $snapshotData[$key . '_url'] = $file->link;
                }
            }
            $application->snapshot_data = $snapshotData;

            return [
                'application' => $application,
                'files'       => $files,
            ];
        });
    }

    /**
     * @inheritDoc
     */
    public function getDriverGroups(): ServiceReturn
    {
        return $this->execute(function () {
            return $this->driverGroupRepository->getAllGroups();
        });
    }

    /**
     * @inheritDoc
     */
    public function getRegistrationServices(?int $vehicleTypeId = null): ServiceReturn
    {
        return $this->execute(function () use ($vehicleTypeId) {
            if ($vehicleTypeId !== null) {
                // Validate vehicle type hợp lệ
                $vehicleType = \App\Modules\User\Model\Enums\VehicleType::tryFrom($vehicleTypeId);
                if ($vehicleType === null || $vehicleType === \App\Modules\User\Model\Enums\VehicleType::Unknown) {
                    $this->throw('Loại xe không hợp lệ.', 422);
                }

                return \App\Modules\Driver\Model\Enums\DriverServiceType::getListByVehicleType($vehicleTypeId);
            }

            return \App\Modules\Driver\Model\Enums\DriverServiceType::getList();
        });
    }
}
