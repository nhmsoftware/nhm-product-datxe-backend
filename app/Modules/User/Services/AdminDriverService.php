<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\User\DTO\Admin\ListDriversDTO;
use App\Modules\User\DTO\Admin\ApproveDriverDTO;
use App\Modules\User\DTO\Admin\RejectDriverDTO;
use App\Modules\User\DTO\Admin\UpdateDriverStatusDTO;
use App\Modules\User\DTO\Admin\AssignDriverGroupDTO;
use App\Modules\User\Events\DriverApplicationApproved;
use App\Modules\User\Events\DriverApplicationRejected;
use App\Modules\User\Events\UserStatusUpdated;
use App\Modules\User\Model\Enums\KycStatus;
use App\Modules\User\Model\Enums\KycType;
use App\Modules\User\Interfaces\AdminDriverServiceInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\Driver\Interfaces\DriverRegistrationServiceInterface;
use App\Modules\Driver\DTO\ApproveRegistrationDTO;

final class AdminDriverService extends BaseService implements AdminDriverServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly DriverRegistrationServiceInterface $driverRegistrationService,
    ) {}

    /**
     * @inheritDoc
     */
    public function listDrivers(ListDriversDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $paginator = $this->userRepository->findDrivers($dto->toArray(), $dto->perPage);
            
            // Map data để Frontend dễ sử dụng
            $paginator->getCollection()->transform(function ($user) {
                $latestKyc = $user->userReviewApplications->first();
                return [
                    'id'                => $user->id,
                    'full_name'         => $user->full_name,
                    'phone'             => $user->phone,
                    'email'             => $user->email,
                    'is_active'         => $user->is_active,
                    'lock_reason'       => $user->lock_reason,
                    'lock_expired_at'   => $user->lock_expired_at?->toIso8601String(),
                    'driver_group_type' => $user->driverProfile?->driver_group_type,
                    'group_label'       => $user->driverProfile?->driver_group_type === 1 ? 'Xe nhà' : ($user->driverProfile?->driver_group_type === 2 ? 'Đối tác' : 'Chưa gán'),
                    'vehicle_type'      => $user->driverProfile?->vehicle_type?->value,
                    'vehicle_type_label'=> $user->driverProfile?->vehicle_type?->getLabel(),
                    'vehicle_name'      => $user->driverProfile?->vehicle_name,
                    'vehicle_number'    => $user->driverProfile?->vehicle_number,
                    'kyc_status'        => $latestKyc?->kyc_status?->value ?? 0,
                    'kyc_status_label'  => $latestKyc?->kyc_status?->label() ?? 'Chưa có hồ sơ',
                    'created_at'        => $user->created_at?->toIso8601String(),
                ];
            });

            return $paginator;
        });
    }

    /**
     * @inheritDoc
     */
    public function approveDriver(ApproveDriverDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->userRepository->findById($dto->userId, relations: ['userReviewApplications']);
            $this->validate($user !== null, 'Tài xế không tồn tại.', 404);

            $latestApplication = $user->userReviewApplications()
                ->where('kyc_type', KycType::Driver->value)
                ->latest()
                ->first();

            $this->validate($latestApplication !== null, 'Tài xế chưa nộp hồ sơ đăng ký.', 404);
            
            if ($latestApplication->kyc_status === KycStatus::Approved) {
                $this->validate(false, 'Hồ sơ tài xế đã được duyệt trước đó.', 400);
            }

            $this->validate($latestApplication->kyc_status === KycStatus::Pending, 'Hồ sơ tài xế đang không ở trạng thái chờ duyệt.', 400);

            // Gọi DriverRegistrationService để thực hiện quy trình duyệt đầy đủ (Status -> Role -> DriverProfile)
            $approveDto = new ApproveRegistrationDTO(
                applicationId: (int) $latestApplication->id,
                driverGroupId: null // Mặc định là Đối tác như yêu cầu
            );

            $result = $this->driverRegistrationService->approveRegistration($approveDto);
            
            if ($result->isError()) {
                $this->throw($result->getMessage(), $result->getCode());
            }

            // Phát sự kiện realtime
            DriverApplicationApproved::dispatch($dto->userId);

            return [
                'user_id' => $dto->userId,
                'message' => 'Duyệt tài xế thành công và đã tạo hồ sơ vận hành.',
            ];
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function rejectDriver(RejectDriverDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->userRepository->findById($dto->userId, relations: ['userReviewApplications']);
            $this->validate($user !== null, 'Tài xế không tồn tại.', 404);

            $latestApplication = $user->userReviewApplications()
                ->where('kyc_type', KycType::Driver->value)
                ->latest()
                ->first();

            $this->validate($latestApplication !== null, 'Tài xế không tồn tại.', 404);
            
            if ($latestApplication->kyc_status !== KycStatus::Pending) {
                $this->validate(false, 'Hồ sơ tài xế đã được xử lý trước đó.', 400);
            }

            $success = $this->userRepository->rejectDriverApplication($dto->userId, $dto->reason);
            $this->validate($success, 'Không thể từ chối tài xế. Vui lòng thử lại.', 500);

            // Phát sự kiện realtime
            DriverApplicationRejected::dispatch($dto->userId, $dto->reason);

            return [
                'user_id' => $dto->userId,
                'message' => 'Từ chối tài xế thành công.',
            ];
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function getDriverDetail(string|int $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $user = $this->userRepository->findById($userId, relations: ['driverProfile']);
            $this->validate($user !== null, 'Tài xế không tồn tại.', 404);

            $driverProfile = $user->driverProfile;
            $latestKyc = $user->userReviewApplications()->latest()->first();
            
            $kycPhotos = [];
            if ($latestKyc) {
                // Load files liên quan
                $files = \App\Modules\Driver\Model\FileRecord::where('fileable_id', $latestKyc->id)
                    ->where('fileable_type', '>=', 2) // Các loại ảnh driver kyc
                    ->get();
                
                foreach ($files as $file) {
                    $key = $file->fileable_type->getRegisterKey();
                    if ($key) {
                        $kycPhotos[$key . '_url'] = $file->link;
                    }
                }
            }

            // Fix avatar URL
            $avatar = $user->avatar;
            if ($avatar && !str_starts_with($avatar, 'http')) {
                $avatar = rtrim(config('app.url'), '/') . '/storage/' . ltrim($avatar, '/');
            }
            if (!$avatar && isset($kycPhotos['portrait_url'])) {
                $avatar = $kycPhotos['portrait_url'];
            }

            // Fix license images
            $licenseFront = $kycPhotos['driver_license_url'] ?? null;
            if (!$licenseFront && $driverProfile?->license_front_image) {
                $licenseFront = rtrim(config('app.url'), '/') . '/storage/' . ltrim($driverProfile->license_front_image, '/');
            }
            
            $licenseBack = $kycPhotos['cccd_front_url'] ?? null;
            if (!$licenseBack && $driverProfile?->license_back_image) {
                $licenseBack = rtrim(config('app.url'), '/') . '/storage/' . ltrim($driverProfile->license_back_image, '/');
            }

            return [
                'id'                 => $user->id,
                'full_name'          => $user->full_name,
                'phone'              => $user->phone,
                'email'              => $user->email,
                'gender'             => $user->gender?->value,
                'gender_label'       => $user->gender?->label(),
                'address'            => $user->address,
                'avatar'             => $avatar,
                'vehicle_info'       => [
                    'vehicle_type'   => $driverProfile?->vehicle_type?->value,
                    'vehicle_name'   => $driverProfile?->vehicle_name,
                    'vehicle_number' => $driverProfile?->vehicle_number,
                    'vehicle_color'  => $driverProfile?->vehicle_color?->label(),
                ],
                'license_info'       => [
                    'license_number'      => $driverProfile?->license_number,
                    'license_front_image' => $licenseFront,
                    'license_back_image'  => $licenseBack,
                ],
                'driver_group_type'  => $driverProfile?->driver_group_type,
                'group_label'        => $driverProfile?->driver_group_type === 1 ? 'Xe nhà' : ($driverProfile?->driver_group_type === 2 ? 'Đối tác' : 'Chưa gán'),
                'is_active'          => $user->is_active,
                'lock_reason'        => $user->lock_reason,
                'lock_expired_at'    => $user->lock_expired_at?->toIso8601String(),
                'kyc_status'         => $latestKyc?->kyc_status?->value,
                'kyc_status_label'   => $latestKyc?->kyc_status?->label() ?? 'Chưa có hồ sơ',
                'kyc_cancel_reason'  => $latestKyc?->cancel_reason,
                'kyc_photos'         => $kycPhotos,
                'snapshot_data'      => $latestKyc?->snapshot_data,
                'created_at'         => $user->created_at?->toIso8601String(),
            ];
        });
    }

    /**
     * @inheritDoc
     */
    public function updateStatus(UpdateDriverStatusDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null, 'Tài xế không tồn tại.', 404);
            $this->validate($user->role === \App\Modules\User\Model\Enums\UserRole::Driver, 'Người dùng không phải là tài xế.', 400);

            if ($user->is_active === $dto->isActive) {
                $this->validate(false, 'Trạng thái tài khoản đã được cập nhật trước đó.', 400);
            }

            $updateData = ['is_active' => $dto->isActive];

            if (!$dto->isActive) {
                $lockedDays = $dto->lockedDays ?? 2;
                $updateData['lock_reason'] = $dto->lockReason;
                $updateData['locked_days'] = $lockedDays;
                $updateData['locked_at'] = now();
                $updateData['lock_expired_at'] = now()->addDays($lockedDays);
            } else {
                $updateData['lock_reason'] = null;
                $updateData['locked_days'] = 0;
                $updateData['locked_at'] = null;
                $updateData['lock_expired_at'] = null;
            }

            $success = $this->userRepository->updateActiveStatus($dto->userId, $updateData);
            $this->validate($success, 'Không thể cập nhật trạng thái tài khoản. Vui lòng thử lại.', 500);

            // Phát sự kiện realtime
            UserStatusUpdated::dispatch($dto->userId, $dto->isActive, $updateData['lock_reason'] ?? null, $updateData['lock_expired_at']?->toIso8601String());

            $message = $dto->isActive ? 'Mở khóa tài khoản tài xế thành công.' : 'Khóa tài khoản tài xế thành công.';

            return [
                'user_id'   => $dto->userId,
                'is_active' => $dto->isActive,
                'message'   => $message,
            ];
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function assignDriverGroup(AssignDriverGroupDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->userRepository->findById($dto->userId, relations: ['driverProfile', 'userReviewApplications']);
            $this->validate($user !== null, 'Tài xế không tồn tại.', 404);

            $driverProfile = $user->driverProfile;
            
            // Trường hợp hy hữu: Đã duyệt hồ sơ nhưng chưa có DriverProfile (do lỗi logic phiên bản cũ)
            // Chúng ta sẽ tự động tạo Profile từ snapshot của hồ sơ đã duyệt
            if ($driverProfile === null) {
                $latestApprovedApp = $user->userReviewApplications()
                    ->where('kyc_type', KycType::Driver->value)
                    ->where('kyc_status', KycStatus::Approved)
                    ->latest()
                    ->first();

                if ($latestApprovedApp) {
                    // Cần tạo Profile thông qua DriverRegistrationService
                    // Nhưng Service đó validate trạng thái Pending, nên ta sẽ tạo thủ công ở đây hoặc cập nhật Service
                    // Để đảm bảo tính nhất quán, ta sẽ gán group trực tiếp sau khi đảm bảo Profile tồn tại
                    
                    // Giả lập DTO để gọi approve (nhưng vì kyc_status là Approved nên sẽ fail validation)
                    // Vậy ta nên fix dữ liệu bằng cách gọi UserRepository tạo profile
                    // Hoặc đơn giản là báo lỗi yêu cầu admin liên hệ kỹ thuật nếu kyc_status=Approved mà profile=null
                    
                    $this->validate(false, 'Hồ sơ đã duyệt nhưng thiếu thông tin vận hành. Vui lòng liên hệ kỹ thuật để đồng bộ lại dữ liệu cho tài xế này.', 400);
                }

                $this->validate($driverProfile !== null, 'Vui lòng duyệt hồ sơ tài xế trước khi gán đội xe.', 400);
            }

            if ($driverProfile->driver_group_type === $dto->groupType) {
                $label = $dto->groupType === \App\Modules\User\Model\Enums\DriverGroupType::INTERNAL ? 'đội xe nhà' : 'đối tác';
                $this->validate(false, "Tài xế đã thuộc {$label}.", 400);
            }

            $success = $this->userRepository->updateDriverGroup($dto->userId, $dto->groupType);
            $this->validate($success, 'Không thể gán đội xe. Vui lòng thử lại.', 500);

            return [
                'user_id'    => $dto->userId,
                'group_type' => $dto->groupType->value,
                'message'    => 'Cập nhật đội xe thành công.',
            ];
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function exportDrivers(ListDriversDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            // Lấy tất cả (không phân trang)
            $drivers = $this->userRepository->findDrivers($dto->toArray(), 9999);
            
            $data = $drivers->getCollection()->map(function ($user) {
                $latestKyc = $user->userReviewApplications->first();
                return [
                    'ID'         => $user->id,
                    'Họ tên'     => $user->full_name,
                    'SĐT'        => $user->phone,
                    'Email'      => $user->email,
                    'Trạng thái' => $user->is_active ? 'Hoạt động' : 'Bị khóa',
                    'KYC'        => $latestKyc?->kyc_status?->label() ?? 'N/A',
                    'Ngày tạo'   => $user->created_at?->format('d/m/Y H:i'),
                ];
            });

            return [
                'items' => $data,
                'total' => $data->count()
            ];
        });
    }
}
