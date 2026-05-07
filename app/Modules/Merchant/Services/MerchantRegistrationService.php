<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Auth\Interfaces\AuthOtpRepositoryInterface;
use App\Modules\Auth\Interfaces\AuthServiceInterface;
use App\Modules\Merchant\DTO\RegisterMerchantDTO;
use App\Modules\Merchant\Events\MerchantRegistrationSubmitted;
use App\Modules\Merchant\Interfaces\MerchantRegistrationServiceInterface;
use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\Driver\Interfaces\DriverRegistrationRepositoryInterface;
use App\Modules\Driver\Interfaces\FileRecordRepositoryInterface;
use App\Modules\Driver\Model\Enums\KycType;
use App\Modules\Driver\Model\Enums\KycStatus;
use App\Modules\Driver\Model\Enums\FileableType;
use App\Modules\Driver\Model\Enums\FileDisk;
use App\Modules\User\Model\Enums\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final class MerchantRegistrationService extends BaseService implements MerchantRegistrationServiceInterface
{
    private const STORAGE_PATH = 'merchant-kyc';

    public function __construct(
        private readonly MerchantRepositoryInterface           $merchantRepository,
        private readonly UserRepositoryInterface               $userRepository,
        private readonly AuthServiceInterface                   $authService,
        private readonly AuthOtpRepositoryInterface            $authOtpRepository,
        private readonly DriverRegistrationRepositoryInterface $driverRegistrationRepository,
        private readonly FileRecordRepositoryInterface         $fileRecordRepository,
    ) {}

    public function submitRegistration(RegisterMerchantDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            // 0. Kiểm tra OTP đã xác thực trước đó (UC-52 quy định tách riêng Verify OTP)
            $lastOtp = $this->authOtpRepository->getLastVerified($dto->phone, \App\Modules\User\Model\Enums\UserOtpType::VERIFY_MERCHANT_REGISTER);
            $this->validate($lastOtp !== null, 'Vui lòng xác thực số điện thoại bằng mã OTP trước khi đăng ký.', 400);
            
            // Đánh dấu OTP đã sử dụng
            $this->authOtpRepository->markLatestAsUsed($dto->phone, \App\Modules\User\Model\Enums\UserOtpType::VERIFY_MERCHANT_REGISTER);

            // 1. Kiểm tra User tồn tại
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null, 'Không tìm thấy thông tin người dùng.', 404);
            $this->validate(!$user->isMerchant(), 'Bạn đã là Merchant.', 400);

            // 2. Kiểm tra hồ sơ đang chờ duyệt
            $existingApp = $this->driverRegistrationRepository->findActiveApplicationByUser($dto->userId, KycType::MERCHANTS);
            $this->validate($existingApp === null, 'Bạn đã có hồ sơ đang chờ xét duyệt.', 409);

            // 3. Kiểm tra CCCD không trùng
            $citizenIdExists = $this->merchantRepository->isCitizenIdExists($dto->citizenId, $dto->userId);
            $this->validate(!$citizenIdExists, 'CCCD đã được sử dụng.', 400);

            // 4. Kiểm tra tên cửa hàng không trùng
            $storeNameExists = $this->merchantRepository->isStoreNameExists($dto->storeName, $dto->userId);
            $this->validate(!$storeNameExists, 'Tên cửa hàng đã tồn tại.', 400);

            // 5. Tạo snapshot data
            $snapshotData = [
                'full_name'     => $dto->fullName,
                'phone'         => $dto->phone,
                'citizen_id'    => $dto->citizenId,
                'store_name'    => $dto->storeName,
                'store_address' => $dto->storeAddress,
                'business_type' => $dto->businessType,
                'submitted_at'  => now()->toISOString(),
            ];

            // 6. Tạo hồ sơ Review (Tận dụng quy trình của Driver)
            $application = $this->driverRegistrationRepository->createDriverApplication(
                userId:       $dto->userId,
                snapshotData: $snapshotData,
                kycType:      KycType::MERCHANTS,
            );

            // 7. Upload + lưu tài liệu
            $this->storeAllDocuments($application->id, $dto->files);

            // 8. Phát event
            event(new MerchantRegistrationSubmitted(
                userId:        $dto->userId,
                applicationId: (string) $application->id,
                storeName:     $dto->storeName
            ));

            return [
                'application_id' => (string) $application->id,
                'status'         => KycStatus::PENDING->getLabel(),
                'message'        => 'Đăng ký thành công. Vui lòng chờ xét duyệt.',
            ];
        }, useTransaction: true);
    }

    private function storeAllDocuments(int $applicationId, array $files): void
    {
        $typeMap = [
            'citizen_id_image'       => FileableType::MERCHANT_REVIEW_CCCD,
            'business_license_image' => FileableType::MERCHANT_REVIEW_BUSINESS_LICENSE,
            'store_image'            => FileableType::MERCHANT_REVIEW_STORE_IMAGE,
        ];

        foreach ($typeMap as $key => $fileableType) {
            /** @var UploadedFile|null $file */
            $file = $files[$key] ?? null;

            if (!$file instanceof UploadedFile) {
                continue;
            }

            $storedPath = $this->uploadToStorage($file, $applicationId);

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

    public function getApplications(): ServiceReturn
    {
        return $this->execute(function () {
            return $this->driverRegistrationRepository->getModelInstance()
                ->where('kyc_type', KycType::MERCHANTS->value)
                ->where('kyc_status', KycStatus::PENDING->value)
                ->with('user')
                ->latest()
                ->get();
        });
    }

    public function getApplicationDetails(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $application = $this->driverRegistrationRepository->findByIdWithUser($id);
            $this->validate($application !== null, 'Không tìm thấy hồ sơ.', 404);
            $this->validate($application->kyc_type === KycType::MERCHANTS, 'Hồ sơ không hợp lệ.', 400);

            $files = $this->fileRecordRepository->findByApplicationId((int) $id);

            return [
                'application' => $application,
                'files'       => $files,
            ];
        });
    }

    public function approveRegistration(string $applicationId): ServiceReturn
    {
        return $this->execute(function () use ($applicationId) {
            $application = $this->driverRegistrationRepository->findById($applicationId);
            $this->validate($application !== null, 'Không tìm thấy hồ sơ.', 404);
            $this->validate($application->kyc_status->isPending(), 'Hồ sơ không ở trạng thái chờ duyệt.', 400);

            $userId = $application->user_id;
            $snapshot = $application->snapshot_data;

            // 1. Cập nhật trạng thái application
            $this->driverRegistrationRepository->updateStatus($application->id, KycStatus::APPROVED);

            // 2. Nâng cấp User role
            $this->userRepository->updateRole($userId, UserRole::Merchants);
            
            // 3. Cập nhật CCCD cho User
            $this->userRepository->updateById($userId, [
                'citizen_id' => $snapshot['citizen_id']
            ]);

            // 4. Lấy thông tin file đã upload
            $files = $this->fileRecordRepository->findByApplicationId((int) $application->id);
            $citizenIdImage = $files->firstWhere('fileable_type', FileableType::MERCHANT_REVIEW_CCCD->value)?->path;
            $licenseImage   = $files->firstWhere('fileable_type', FileableType::MERCHANT_REVIEW_BUSINESS_LICENSE->value)?->path;
            $storeImage     = $files->firstWhere('fileable_type', FileableType::MERCHANT_REVIEW_STORE_IMAGE->value)?->path;

            // 5. Tạo Merchant Profile
            $this->merchantRepository->create([
                'user_id'                => $userId,
                'store_name'             => $snapshot['store_name'],
                'store_address'          => $snapshot['store_address'],
                'business_type'          => $snapshot['business_type'],
                'citizen_id_image'       => $citizenIdImage,
                'business_license_image' => $licenseImage,
                'store_image'            => $storeImage,
                'status'                 => \App\Modules\User\Model\Enums\KycStatus::Approved->value,
                'is_open'                => false,
            ]);

            return [
                'user_id' => $userId,
                'status'  => 'Đã phê duyệt thành công',
            ];
        }, useTransaction: true);
    }

    public function rejectRegistration(string $applicationId, string $reason): ServiceReturn
    {
        return $this->execute(function () use ($applicationId, $reason) {
            $application = $this->driverRegistrationRepository->findById($applicationId);
            $this->validate($application !== null, 'Không tìm thấy hồ sơ.', 404);
            $this->validate($application->kyc_status->isPending(), 'Hồ sơ không ở trạng thái chờ duyệt.', 400);

            $this->driverRegistrationRepository->updateStatus($application->id, KycStatus::REJECTED, $reason);

            return [
                'status' => 'Đã từ chối hồ sơ',
            ];
        });
    }
}
