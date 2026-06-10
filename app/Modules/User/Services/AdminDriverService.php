<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Core\Helpers\FileHelper;
use App\Core\Logs\Logging;
use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Driver\Interfaces\DriverRegistrationRepositoryInterface;
use App\Modules\Driver\Model\Enums\KycType as DriverKycType;
use App\Modules\User\DTO\Admin\ListDriversDTO;
use App\Modules\User\DTO\Admin\CreateDriverDTO;
use App\Modules\User\DTO\Admin\ApproveDriverDTO;
use App\Modules\User\DTO\Admin\RejectDriverDTO;
use App\Modules\User\DTO\Admin\UpdateDriverDTO;
use App\Modules\User\DTO\Admin\UpdateDriverStatusDTO;
use App\Modules\User\DTO\Admin\AssignDriverGroupDTO;
use App\Modules\User\Events\DriverApplicationApproved;
use App\Modules\User\Events\DriverApplicationRejected;
use App\Modules\User\Events\UserStatusUpdated;
use App\Modules\Ride\Services\VehicleTypeCatalogService;
use App\Modules\User\Interfaces\DriverProfileRepositoryInterface;
use App\Modules\User\Model\Enums\KycStatus;
use App\Modules\User\Model\Enums\KycType;
use App\Modules\User\Interfaces\AdminDriverServiceInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\DriverStatus;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\Driver\Interfaces\DriverRegistrationServiceInterface;
use App\Modules\Driver\Interfaces\FileRecordRepositoryInterface;
use App\Modules\Driver\DTO\ApproveRegistrationDTO;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class AdminDriverService extends BaseService implements AdminDriverServiceInterface
{
    private const DRIVER_ID_RETRY_TIMES = 3;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly DriverProfileRepositoryInterface $driverProfileRepository,
        private readonly DriverRegistrationRepositoryInterface $driverRegistrationRepository,
        private readonly DriverRegistrationServiceInterface $driverRegistrationService,
        private readonly FileRecordRepositoryInterface $fileRecordRepository,
        private readonly VehicleTypeCatalogService $vehicleTypeCatalogService,
    ) {}

    /**
     * @inheritDoc
     */
    public function listDrivers(ListDriversDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $this->authorizeAdminOrFail();
            $paginator = $this->userRepository->findDrivers($dto->toArray(), $dto->perPage);
            
            // Map data để Frontend dễ sử dụng
            $paginator->getCollection()->transform(function ($user) {
                $latestKyc = $user->userReviewApplications->first();
                $snapshot = is_array($latestKyc?->snapshot_data) ? $latestKyc->snapshot_data : [];
                $driverGroupType = $user->driverProfile?->driver_group_type ?? ($snapshot['driver_group_type'] ?? null);
                $vehicleTypeValue = $user->driverProfile?->getRawOriginal('vehicle_type') ?? ($snapshot['vehicle_type'] ?? null);
                $vehicleTypeLabel = $vehicleTypeValue ? $this->vehicleTypeCatalogService->getLabelById((int) $vehicleTypeValue) : null;
                return [
                    'id'                => $user->id,
                    'full_name'         => $user->full_name,
                    'phone'             => $user->phone,
                    'email'             => $user->email,
                    'is_active'         => $user->is_active,
                    'lock_reason'       => $user->lock_reason,
                    'lock_expired_at'   => $user->lock_expired_at?->toIso8601String(),
                    'driver_group_type' => $driverGroupType,
                    'group_label'       => $driverGroupType === 1 ? 'Xe nhà' : ($driverGroupType === 2 ? 'Đối tác' : 'Chưa gán'),
                    'vehicle_type'      => $vehicleTypeValue,
                    'vehicle_type_label'=> $vehicleTypeLabel,
                    'vehicle_name'      => $user->driverProfile?->vehicle_name ?? ($snapshot['vehicle_name'] ?? null),
                    'vehicle_number'    => $user->driverProfile?->vehicle_number ?? ($snapshot['vehicle_number'] ?? null),
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
    public function createDriver(CreateDriverDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $this->authorizeAdminOrFail();

            $this->validate(
                !$this->userRepository->existsByPhone($dto->phone),
                'Số điện thoại này đã tồn tại trong hệ thống.',
                409
            );

            if ($dto->email !== null) {
                $this->validate(
                    $this->userRepository->findByEmail($dto->email) === null,
                    'Email này đã tồn tại trong hệ thống.',
                    409
                );
            }

            $plainPassword = $dto->password ?: $this->generateTemporaryPassword();
            $user = $this->persistDriverWithRetry($dto, $plainPassword);

            Logging::userActivity(
                action: 'admin_create_driver',
                description: "Tạo tài xế #{$user->id}",
                userId: (string) (request()->user()?->id ?? 'guest')
            );

            return $this->success([
                'id' => $user->id,
                'full_name' => $user->full_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'gender' => $user->gender?->value,
                'gender_label' => $user->gender?->label(),
                'birthday' => $user->birthday?->format('Y-m-d'),
                'address' => $user->address,
                'is_active' => $user->is_active,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'driver_group_type' => $dto->driverGroupType?->value,
                'driver_group_label' => $dto->driverGroupType?->label() ?? 'Chưa gán',
                'kyc_status' => KycStatus::NotSubmitted->value,
                'kyc_status_label' => KycStatus::NotSubmitted->label(),
                'temporary_password' => $dto->password ? null : $plainPassword,
                'created_at' => $user->created_at?->toIso8601String(),
            ], 'Tạo tài xế thành công.');
        }, useTransaction: false);
    }

    /**
     * @inheritDoc
     */
    public function approveDriver(ApproveDriverDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $this->authorizeAdminOrFail();
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
            $this->authorizeAdminOrFail();
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
            $this->authorizeAdminOrFail();
            $user = $this->userRepository->findById($userId, relations: ['driverProfile']);
            $this->validate($user !== null && $user->role === UserRole::Driver, 'Không tìm thấy tài xế.', 404);

            $driverProfile = $user->driverProfile;
            $latestKyc = $user->userReviewApplications()
                ->where('kyc_type', KycType::Driver->value)
                ->latest()
                ->first();
            $snapshot = is_array($latestKyc?->snapshot_data) ? $latestKyc->snapshot_data : [];
            
            $kycPhotos = [];
            if ($latestKyc) {
                // Load files liên quan
                $files = $this->fileRecordRepository->findByApplicationId((int) $latestKyc->id)
                    ->filter(fn($file) => $file->fileable_type->value >= 2);
                
                foreach ($files as $file) {
                    $key = $file->fileable_type->getRegisterKey();
                    if ($key) {
                        $kycPhotos[$key . '_url'] = $file->link;
                    }
                }
            }

            // Fix avatar URL
            $avatar = $user->avatar;
            if ($avatar && !filter_var($avatar, FILTER_VALIDATE_URL)) {
                $avatar = FileHelper::serveUrl($avatar);
            }
            if (!$avatar && isset($kycPhotos['portrait_url'])) {
                $avatar = $kycPhotos['portrait_url'];
            }

            $vehicleTypeValue = $driverProfile?->getRawOriginal('vehicle_type') ?? ($snapshot['vehicle_type'] ?? null);
            $vehicleTypeLabel = $vehicleTypeValue ? $this->vehicleTypeCatalogService->getLabelById((int) $vehicleTypeValue) : null;
            $vehicleColorValue = $driverProfile?->vehicle_color?->value ?? ($snapshot['vehicle_color'] ?? null);
            $vehicleColorLabel = $driverProfile?->vehicle_color?->label()
                ?? ($vehicleColorValue ? \App\Modules\User\Model\Enums\VehicleColor::tryFrom((int) $vehicleColorValue)?->label() : null);
            $driverGroupType = $driverProfile?->driver_group_type ?? ($snapshot['driver_group_type'] ?? null);
            $serviceRegistrations = collect($snapshot['services'] ?? [])
                ->map(fn($id) => \App\Modules\Driver\Model\Enums\DriverServiceType::tryFrom((int) $id))
                ->filter()
                ->map(fn($service) => [
                    'id' => $service->value,
                    'label' => $service->getLabel(),
                ])
                ->values()
                ->toArray();

            // Fix license images — dùng FileHelper thay vì ghép thủ công
            // Lưu ý: DriverProfile đã có accessor getLicenseFrontImageAttribute
            // nên cần lấy raw path từ DB để tránh double-convert
            $licenseFront = $kycPhotos['driver_license_url'] ?? null;
            if (!$licenseFront && $driverProfile?->getRawOriginal('license_front_image')) {
                $licenseFront = FileHelper::serveUrl($driverProfile->getRawOriginal('license_front_image'));
            }

            $licenseBack = $kycPhotos['cccd_front_url'] ?? null;
            if (!$licenseBack && $driverProfile?->getRawOriginal('license_back_image')) {
                $licenseBack = FileHelper::serveUrl($driverProfile->getRawOriginal('license_back_image'));
            }

            return [
                'id'                 => $user->id,
                'full_name'          => $user->full_name,
                'phone'              => $user->phone,
                'email'              => $user->email,
                'gender'             => $user->gender?->value,
                'gender_label'       => $user->gender?->label(),
                'birthday'           => $user->birthday?->format('Y-m-d'),
                'address'            => $user->address,
                'avatar'             => $avatar,
                'vehicle_info'       => [
                    'vehicle_type'   => $vehicleTypeValue,
                    'vehicle_type_label' => $vehicleTypeLabel,
                    'vehicle_name'   => $driverProfile?->vehicle_name ?? ($snapshot['vehicle_name'] ?? null),
                    'vehicle_number' => $driverProfile?->vehicle_number ?? ($snapshot['vehicle_number'] ?? null),
                    'vehicle_color'  => $vehicleColorLabel,
                    'vehicle_color_value' => $vehicleColorValue,
                    'vehicle_year'   => $snapshot['vehicle_year'] ?? null,
                ],
                'license_info'       => [
                    'license_number'      => $driverProfile?->license_number,
                    'license_front_image' => $licenseFront,
                    'license_back_image'  => $licenseBack,
                ],
                'driver_group_type'  => $driverGroupType,
                'group_label'        => $driverGroupType === 1 ? 'Xe nhà' : ($driverGroupType === 2 ? 'Đối tác' : 'Chưa gán'),
                'is_active'          => $user->is_active,
                'lock_reason'        => $user->lock_reason,
                'lock_expired_at'    => $user->lock_expired_at?->toIso8601String(),
                'kyc_status'         => $latestKyc?->kyc_status?->value,
                'kyc_status_label'   => $latestKyc?->kyc_status?->label() ?? 'Chưa có hồ sơ',
                'kyc_cancel_reason'  => $latestKyc?->cancel_reason,
                'kyc_photos'         => $kycPhotos,
                'snapshot_data'      => $latestKyc?->snapshot_data,
                'citizen_id'         => $snapshot['citizen_id'] ?? null,
                'registered_services'=> $serviceRegistrations,
                'submitted_at'       => $snapshot['submitted_at'] ?? null,
                'created_at'         => $user->created_at?->toIso8601String(),
            ];
        });
    }

    /**
     * @inheritDoc
     */
    public function updateDriver(UpdateDriverDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $this->authorizeAdminOrFail();

            $user = $this->userRepository->findById($dto->userId, relations: ['driverProfile', 'userReviewApplications']);
            $this->validate($user !== null && $user->role === UserRole::Driver, 'Không tìm thấy tài xế.', 404);

            $existingPhoneUser = $this->userRepository->findByPhone($dto->phone);
            if ($existingPhoneUser && (string) $existingPhoneUser->id !== (string) $user->id) {
                $this->throw('Số điện thoại này đã được sử dụng.', 409);
            }

            if ($dto->email !== null) {
                $existingEmailUser = $this->userRepository->findByEmail($dto->email);
                if ($existingEmailUser && (string) $existingEmailUser->id !== (string) $user->id) {
                    $this->throw('Email này đã được sử dụng.', 409);
                }
            }

            $userData = [
                'phone' => $dto->phone,
                'email' => $dto->email,
                'address' => $dto->address,
            ];

            if ($dto->isActive !== null && $dto->isActive !== $user->is_active) {
                $this->validate(
                    !$this->userRepository->hasActiveRideForDriver($user->id) && !$this->userRepository->hasActiveFoodOrderForDriver($user->id),
                    'Không thể khóa tài xế đang có chuyến hoặc đơn đang xử lý.',
                    409
                );

                $userData['is_active'] = $dto->isActive;

                if ($dto->isActive) {
                    $userData['lock_reason'] = null;
                    $userData['locked_days'] = null;
                    $userData['locked_at'] = null;
                    $userData['lock_expired_at'] = null;
                } else {
                    $userData['lock_reason'] = $dto->lockReason;
                    $userData['locked_days'] = null;
                    $userData['locked_at'] = now();
                    $userData['lock_expired_at'] = null;
                }
            }

            $user->update($userData);

            $user->customerProfile()?->update([
                'full_name' => $dto->fullName,
                'gender' => $dto->gender?->value,
                'birthday' => $dto->birthday,
                'address' => $dto->address,
            ]);

            if ($user->driverProfile) {
                $driverProfileData = [
                    'full_name' => $dto->fullName,
                    'driver_group_type' => $dto->driverGroupType?->value ?? $user->driverProfile->driver_group_type,
                    'vehicle_type' => $dto->vehicleType ?? $user->driverProfile?->getRawOriginal('vehicle_type'),
                    'vehicle_color' => $dto->vehicleColor?->value ?? $user->driverProfile->vehicle_color?->value,
                    'vehicle_name' => $dto->vehicleName ?? $user->driverProfile->vehicle_name,
                    'vehicle_number' => $dto->vehicleNumber ?? $user->driverProfile->vehicle_number,
                ];

                $user->driverProfile()->update($driverProfileData);
            }

            $this->upsertDriverDraftApplication($user->id, $dto);

            if ($dto->kycStatus !== null) {
                if ($dto->kycStatus === KycStatus::Approved && $user->driverProfile === null) {
                    $this->materializeDriverProfile($user, $dto);
                }
                $this->syncDriverKycStatus($user->id, $dto->kycStatus);
            }

            $freshUser = $this->userRepository->findById($user->id, relations: ['driverProfile', 'userReviewApplications']);
            $latestKyc = $freshUser?->userReviewApplications()->where('kyc_type', KycType::Driver->value)->latest()->first();

            Logging::userActivity(
                action: 'admin_update_driver',
                description: "Cập nhật tài xế #{$user->id}",
                userId: (string) (request()->user()?->id ?? 'guest')
            );

            return $this->success([
                'id' => $freshUser?->id,
                'full_name' => $freshUser?->full_name,
                'phone' => $freshUser?->phone,
                'email' => $freshUser?->email,
                'gender' => $freshUser?->gender?->value,
                'gender_label' => $freshUser?->gender?->label(),
                'birthday' => $freshUser?->birthday?->format('Y-m-d'),
                'address' => $freshUser?->address,
                'is_active' => $freshUser?->is_active,
                'driver_group_type' => $freshUser?->driverProfile?->driver_group_type,
                'group_label' => $freshUser?->driverProfile?->driver_group_type === 1 ? 'Xe nhà' : ($freshUser?->driverProfile?->driver_group_type === 2 ? 'Đối tác' : 'Chưa gán'),
                'vehicle_type' => $freshUser?->driverProfile?->getRawOriginal('vehicle_type'),
                'vehicle_name' => $freshUser?->driverProfile?->vehicle_name,
                'vehicle_number' => $freshUser?->driverProfile?->vehicle_number,
                'kyc_status' => $latestKyc?->kyc_status?->value ?? KycStatus::NotSubmitted->value,
                'kyc_status_label' => $latestKyc?->kyc_status?->label() ?? KycStatus::NotSubmitted->label(),
                'updated_at' => $freshUser?->updated_at?->toIso8601String(),
            ], 'Cập nhật tài xế thành công.');
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function deleteDriver(string|int $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $this->authorizeAdminOrFail();

            $user = $this->userRepository->findById($userId, relations: ['driverProfile']);
            $this->validate($user !== null && $user->role === UserRole::Driver, 'Không tìm thấy tài xế.', 404);

            $this->validate(
                !$this->userRepository->hasActiveRideForDriver($userId) && !$this->userRepository->hasActiveFoodOrderForDriver($userId),
                'Không thể xóa tài xế đang có chuyến hoặc đơn đang xử lý.',
                409
            );

            $this->userRepository->softDeleteDriver($user);

            Logging::userActivity(
                action: 'admin_delete_driver',
                description: "Xóa mềm tài xế #{$user->id}",
                userId: (string) (request()->user()?->id ?? 'guest')
            );

            return $this->success([
                'user_id' => (string) $user->id,
            ], 'Xóa tài xế thành công.');
        }, useTransaction: true);
    }

    /**
     * @inheritDoc
     */
    public function updateStatus(UpdateDriverStatusDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $this->authorizeAdminOrFail();
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null && $user->role === UserRole::Driver, 'Không tìm thấy tài xế.', 404);
            $this->validate($user->role === \App\Modules\User\Model\Enums\UserRole::Driver, 'Người dùng không phải là tài xế.', 400);

            if ($user->is_active === $dto->isActive) {
                $this->validate(false, 'Trạng thái tài khoản đã được cập nhật trước đó.', 400);
            }

            if ($dto->isActive === false) {
                $this->validate(
                    !$this->userRepository->hasActiveRideForDriver($dto->userId) && !$this->userRepository->hasActiveFoodOrderForDriver($dto->userId),
                    'Không thể khóa tài xế đang có chuyến hoặc đơn đang xử lý.',
                    409
                );
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

            $driverProfile = $this->driverProfileRepository->findByUserId((string) $dto->userId);
            if ($driverProfile !== null) {
                $this->driverProfileRepository->updateStatus(
                    $driverProfile->id,
                    $dto->isActive ? DriverStatus::ACTIVE : DriverStatus::BANNED
                );
            }

            // Phát sự kiện realtime
            UserStatusUpdated::dispatch($dto->userId, $dto->isActive, $updateData['lock_reason'] ?? null, $updateData['lock_expired_at']?->toIso8601String());

            Logging::userActivity(
                action: 'admin_update_driver_status',
                description: "Cập nhật trạng thái tài xế #{$dto->userId} thành " . ($dto->isActive ? 'active' : 'locked'),
                userId: (string) (request()->user()?->id ?? 'guest')
            );

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
            $this->authorizeAdminOrFail();
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

            if ($driverProfile->driver_group_type === $dto->groupType->value) {
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
            $this->authorizeAdminOrFail();
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

    private function authorizeAdminOrFail(): void
    {
        $requestUser = request()->user();

        $this->validate(
            $requestUser !== null && method_exists($requestUser, 'isAdmin') && $requestUser->isAdmin(),
            'Bạn không có quyền thực hiện thao tác này.',
            403
        );
    }

    private function persistDriverWithRetry(CreateDriverDTO $dto, string $plainPassword)
    {
        for ($attempt = 1; $attempt <= self::DRIVER_ID_RETRY_TIMES; $attempt++) {
            DB::beginTransaction();

            try {
                $user = $this->userRepository->create([
                    'phone' => $dto->phone,
                    'email' => $dto->email,
                    'password' => Hash::make($plainPassword),
                    'role' => UserRole::Driver,
                    'is_verified' => true,
                    'is_phone_verified' => true,
                    'is_active' => $dto->isActive ?? true,
                    'address' => $dto->address,
                ]);

                $this->userRepository->createCustomerProfile($user, [
                    'full_name' => $dto->fullName,
                    'gender' => $dto->gender?->value,
                    'birthday' => $dto->birthday,
                    'address' => $dto->address,
                ]);

                DB::commit();

                return $user->fresh();
            } catch (QueryException $e) {
                DB::rollBack();

                if ($this->isDuplicatePhoneException($e)) {
                    $this->throw('Số điện thoại này đã tồn tại trong hệ thống.', 409);
                }

                if ($this->isDuplicateEmailException($e)) {
                    $this->throw('Email này đã tồn tại trong hệ thống.', 409);
                }

                if ($this->isPrimaryKeyCollision($e) && $attempt < self::DRIVER_ID_RETRY_TIMES) {
                    continue;
                }

                if ($this->isPrimaryKeyCollision($e)) {
                    $this->throw('Không thể tạo mã tài xế. Vui lòng thử lại.', 500);
                }

                throw $e;
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        }

        $this->throw('Không thể tạo mã tài xế. Vui lòng thử lại.', 500);
    }

    private function upsertDriverDraftApplication(string|int $userId, UpdateDriverDTO $dto): void
    {
        $hasKycPayload = $dto->driverGroupType !== null
            || $dto->vehicleType !== null
            || $dto->vehicleColor !== null
            || !empty($dto->vehicleName)
            || !empty($dto->vehicleNumber)
            || !empty($dto->note);

        if (!$hasKycPayload) {
            return;
        }

        $application = $this->driverRegistrationRepository->findActiveApplicationByUser($userId, DriverKycType::DRIVER);
        $snapshot = [
            'full_name' => $dto->fullName,
            'phone' => $dto->phone,
            'email' => $dto->email,
            'gender' => $dto->gender?->value,
            'birthday' => $dto->birthday,
            'address' => $dto->address,
            'driver_group_type' => $dto->driverGroupType?->value,
            'vehicle_type' => $dto->vehicleType?->value,
            'vehicle_color' => $dto->vehicleColor?->value,
            'vehicle_name' => $dto->vehicleName,
            'vehicle_number' => $dto->vehicleNumber,
            'note' => $dto->note ?? null,
            'submitted_from_admin' => true,
        ];

        if ($application === null) {
            $this->driverRegistrationRepository->createDriverApplication(
                userId: (string) $userId,
                snapshotData: $snapshot,
                kycType: DriverKycType::DRIVER,
            );
            return;
        }

        $application->update([
            'snapshot_data' => array_merge($application->snapshot_data ?? [], array_filter($snapshot, fn($value) => $value !== null && $value !== '')),
        ]);
    }

    private function syncDriverKycStatus(string|int $userId, KycStatus $status): void
    {
        $application = $this->driverRegistrationRepository->findActiveApplicationByUser($userId, DriverKycType::DRIVER);
        if ($application === null) {
            return;
        }

        $application->update([
            'kyc_status' => match ($status) {
                KycStatus::Pending => \App\Modules\Driver\Model\Enums\KycStatus::PENDING->value,
                KycStatus::Approved => \App\Modules\Driver\Model\Enums\KycStatus::APPROVED->value,
                KycStatus::Rejected => \App\Modules\Driver\Model\Enums\KycStatus::REJECTED->value,
                default => \App\Modules\Driver\Model\Enums\KycStatus::PENDING->value,
            },
        ]);
    }

    private function materializeDriverProfile($user, UpdateDriverDTO $dto): void
    {
        $this->validate($dto->vehicleType !== null, 'Chưa thể duyệt hồ sơ khi chưa có loại xe.', 400);
        $this->validate($dto->vehicleColor !== null, 'Chưa thể duyệt hồ sơ khi chưa có màu xe.', 400);
        $this->validate(!empty($dto->vehicleName), 'Chưa thể duyệt hồ sơ khi chưa có tên xe.', 400);
        $this->validate(!empty($dto->vehicleNumber), 'Chưa thể duyệt hồ sơ khi chưa có biển số xe.', 400);

        $this->userRepository->createDriverProfile($user, [
            'full_name' => $dto->fullName,
            'driver_group_id' => null,
            'driver_group_type' => $dto->driverGroupType?->value ?? \App\Modules\User\Model\Enums\DriverGroupType::PARTNER->value,
            'vehicle_type' => $dto->vehicleType,
            'vehicle_name' => $dto->vehicleName,
            'vehicle_color' => $dto->vehicleColor->value,
            'vehicle_number' => $dto->vehicleNumber,
            'is_online' => false,
            'status' => DriverStatus::ACTIVE->value,
        ]);
    }

    private function generateTemporaryPassword(): string
    {
        return sprintf('Tmp@%06d', random_int(0, 999999));
    }

    private function isPrimaryKeyCollision(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique constraint failed: users.id')
            || str_contains($message, 'users.primary')
            || (str_contains($message, 'duplicate entry') && str_contains($message, 'primary'));
    }

    private function isDuplicatePhoneException(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique constraint failed: users.phone')
            || str_contains($message, 'users.phone')
            || str_contains($message, 'users_phone_unique');
    }

    private function isDuplicateEmailException(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique constraint failed: users.email')
            || str_contains($message, 'users.email')
            || str_contains($message, 'users_email_unique');
    }
}
