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

final class AdminDriverService extends BaseService implements AdminDriverServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
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
                    'id'               => $user->id,
                    'full_name'        => $user->full_name,
                    'phone'            => $user->phone,
                    'email'            => $user->email,
                    'is_active'        => $user->is_active,
                    'driver_group_type' => $user->driverProfile?->driver_group_type,
                    'group_label'      => $user->driverProfile?->driver_group_type === 1 ? 'Xe nhà' : ($user->driverProfile?->driver_group_type === 2 ? 'Đối tác' : 'Chưa gán'),
                    'kyc_status'       => $latestKyc?->kyc_status?->value ?? 0,
                    'kyc_status_label' => $latestKyc?->kyc_status?->label() ?? 'Chưa có hồ sơ',
                    'created_at'       => $user->created_at?->toIso8601String(),
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

            $this->validate($latestApplication !== null, 'Tài xế không tồn tại.', 404);
            
            if ($latestApplication->kyc_status === KycStatus::Approved) {
                $this->validate(false, 'Tài xế đã được duyệt trước đó.', 400);
            }

            $this->validate($latestApplication->kyc_status === KycStatus::Pending, 'Tài xế đang không ở trạng thái chờ duyệt.', 400);

            $success = $this->userRepository->approveDriverApplication($dto->userId);
            $this->validate($success, 'Không thể duyệt tài xế. Vui lòng thử lại.', 500);

            // Phát sự kiện realtime
            DriverApplicationApproved::dispatch($dto->userId);

            return [
                'user_id' => $dto->userId,
                'message' => 'Duyệt tài xế thành công.',
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
            $user = $this->userRepository->findDriverDetailById($userId);
            $this->validate($user !== null, 'Tài xế không tồn tại.', 404);

            $driverProfile = $user->driverProfile;
            $latestKyc = $user->userReviewApplications->first();

            return [
                'id'                 => $user->id,
                'full_name'          => $user->full_name,
                'phone'              => $user->phone,
                'email'              => $user->email,
                'gender'             => $user->gender?->value,
                'gender_label'       => $user->gender?->label(),
                'address'            => $user->address,
                'avatar'             => $user->avatar,
                'vehicle_info'       => [
                    'vehicle_type'   => $driverProfile?->vehicle_type?->value,
                    'vehicle_name'   => $driverProfile?->vehicle_name,
                    'vehicle_number' => $driverProfile?->vehicle_number,
                    'vehicle_color'  => $driverProfile?->vehicle_color?->label(),
                ],
                'license_info'       => [
                    'license_number'      => $driverProfile?->license_number,
                    'license_front_image' => $driverProfile?->license_front_image,
                    'license_back_image'  => $driverProfile?->license_back_image,
                ],
                'is_active'          => $user->is_active,
                'lock_reason'        => $user->lock_reason,
                'lock_expired_at'    => $user->lock_expired_at?->toIso8601String(),
                'kyc_status'         => $latestKyc?->kyc_status?->value,
                'kyc_status_label'   => $latestKyc?->kyc_status?->label() ?? 'Chưa có hồ sơ',
                'kyc_cancel_reason'  => $latestKyc?->cancel_reason,
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

            // Phát sự kiện realtime (UC-78/UC-84 chia sẻ chung event status updated)
            UserStatusUpdated::dispatch($dto->userId, $dto->isActive, $updateData['lock_reason'] ?? null);

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
            $user = $this->userRepository->findById($dto->userId, relations: ['driverProfile']);
            $this->validate($user !== null, 'Tài xế không tồn tại.', 404);

            $driverProfile = $user->driverProfile;
            $this->validate($driverProfile !== null, 'Tài xế không tồn tại.', 404);

            if ($driverProfile->driver_group_type === $dto->groupType) {
                $this->validate(false, 'Tài xế đã thuộc đội xe nhà.', 400);
            }

            $success = $this->userRepository->updateDriverGroup($dto->userId, $dto->groupType);
            $this->validate($success, 'Không thể gán tài xế vào đội xe nhà. Vui lòng thử lại.', 500);

            return [
                'user_id'    => $dto->userId,
                'group_type' => $dto->groupType->value,
                'message'    => 'Gán tài xế vào đội xe nhà thành công.',
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

