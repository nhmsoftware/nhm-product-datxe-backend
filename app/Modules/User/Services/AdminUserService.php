<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\User\DTO\Admin\ListUsersDTO;
use App\Modules\User\DTO\Admin\UpdateUserStatusDTO;
use App\Modules\User\Interfaces\AdminUserServiceInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Events\UserStatusUpdated;

final class AdminUserService extends BaseService implements AdminUserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @inheritDoc
     */
    public function listCustomers(ListUsersDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $paginator = $this->userRepository->findCustomers($dto->toArray(), $dto->perPage);
            
            $paginator->getCollection()->transform(function ($user) {
                return [
                    'id'          => $user->id,
                    'full_name'   => $user->full_name,
                    'phone'       => $user->phone,
                    'email'       => $user->email,
                    'is_active'   => $user->is_active,
                    'total_rides' => $user->customerProfile?->total_rides ?? 0,
                    'created_at'  => $user->created_at?->toIso8601String(),
                ];
            });

            return $paginator;
        });
    }


    /**
     * @inheritDoc
     */
    public function getCustomerDetail(string|int $userId): ServiceReturn
    {
        return $this->execute(function () use ($userId) {
            $user = $this->userRepository->findDetailById($userId);
            $this->validate($user !== null, 'Người dùng không tồn tại.', 404);
            
            return [
                'id'                => $user->id,
                'full_name'         => $user->full_name,
                'phone'             => $user->phone,
                'email'             => $user->email,
                'gender'            => $user->gender?->value,
                'gender_label'      => $user->gender?->label(),
                'address'           => $user->address,
                'avatar'            => $user->avatar,
                'is_active'         => $user->is_active,
                'lock_reason'       => $user->lock_reason,
                'locked_at'         => $user->locked_at?->toIso8601String(),
                'lock_expired_at'   => $user->lock_expired_at?->toIso8601String(),
                'locked_days'       => $user->locked_days,
                'created_at'        => $user->created_at?->toIso8601String(),
                'role'              => $user->role->value,
                'role_label'        => $user->role->label(),
            ];
        });
    }

    /**
     * @inheritDoc
     * UC-78 Lock/Unlock User
     */
    public function updateUserStatus(UpdateUserStatusDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            $user = $this->userRepository->findById($dto->userId);
            $this->validate($user !== null, 'Tài khoản không tồn tại.', 404);

            $updateData = ['is_active' => $dto->isActive];

            if (!$dto->isActive) {
                // Thao tác Lock User
                $this->validate($user->is_active, 'Trạng thái tài khoản đã được cập nhật trước đó.');
                
                $lockedAt = now();
                $lockExpiredAt = null;

                if ($dto->lockExpiredAt) {
                    $lockExpiredAt = \Illuminate\Support\Carbon::parse($dto->lockExpiredAt);
                } else {
                    $lockedDays = $dto->lockedDays ?? 3; // Default to 3 days as per requirement
                    $lockExpiredAt = (clone $lockedAt)->addDays($lockedDays);
                }

                $updateData = array_merge($updateData, [
                    'lock_reason'     => $dto->reason,
                    'locked_days'     => $dto->lockedDays ?? ($dto->lockExpiredAt ? $lockedAt->diffInDays($lockExpiredAt) : 3),
                    'locked_at'       => $lockedAt,
                    'lock_expired_at' => $lockExpiredAt,
                ]);
            } else {
                // Thao tác Unlock User
                $this->validate(!$user->is_active, 'Trạng thái tài khoản đã được cập nhật trước đó.');

                $updateData = array_merge($updateData, [
                    'lock_reason'     => null,
                    'locked_days'     => null,
                    'locked_at'       => null,
                    'lock_expired_at' => null,
                ]);
            }

            $success = $this->userRepository->updateActiveStatus($dto->userId, $updateData);
            $this->validate($success, 'Không thể cập nhật trạng thái tài khoản. Vui lòng thử lại.');

            // Phát sự kiện realtime
            UserStatusUpdated::dispatch(
                $dto->userId,
                $dto->isActive,
                $dto->reason,
                isset($lockExpiredAt) ? $lockExpiredAt->toIso8601String() : null
            );

            return [
                'user_id'   => $dto->userId,
                'is_active' => $dto->isActive,
                'message'   => $dto->isActive ? 'Mở khóa tài khoản thành công.' : 'Khóa tài khoản thành công.',
            ];
        }, useTransaction: true);
    }
}
