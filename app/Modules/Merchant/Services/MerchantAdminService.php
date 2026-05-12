<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Merchant\DTO\MerchantFilterDTO;
use App\Modules\Merchant\Interfaces\MerchantAdminServiceInterface;
use App\Modules\Merchant\Interfaces\MerchantRepositoryInterface;
use App\Modules\User\Interfaces\UserRepositoryInterface;
use App\Modules\User\Model\Enums\KycStatus;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Events\UserStatusUpdated;
use App\Modules\Driver\Interfaces\DriverRegistrationRepositoryInterface;
use App\Modules\Driver\Model\Enums\KycType;
use App\Modules\Driver\Model\Enums\KycStatus as AppKycStatus;

final class MerchantAdminService extends BaseService implements MerchantAdminServiceInterface
{
    public function __construct(
        private readonly MerchantRepositoryInterface           $merchantRepository,
        private readonly UserRepositoryInterface               $userRepository,
        private readonly DriverRegistrationRepositoryInterface $driverRegistrationRepository,
    ) {}

    public function getMerchants(MerchantFilterDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            return $this->merchantRepository->searchMerchants($dto);
        });
    }

    public function getMerchantDetails(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $merchant = $this->merchantRepository->findById($id);
            $this->validate($merchant !== null, 'Merchant không tồn tại.', 404);
            
            $merchant->load(['user', 'user.customerProfile']);
            
            // Lấy thông tin hồ sơ xét duyệt nếu có
            $application = $this->driverRegistrationRepository->findActiveApplicationByUser($merchant->user_id, KycType::MERCHANTS);

            return [
                'merchant'    => $merchant,
                'application' => $application,
            ];
        });
    }

    public function approveMerchant(string $id): ServiceReturn
    {
        return $this->execute(function () use ($id) {
            $merchant = $this->merchantRepository->findById($id);
            $this->validate($merchant !== null, 'Merchant không tồn tại.', 404);
            $this->validate($merchant->status !== KycStatus::Approved, 'Merchant đã được duyệt trước đó.', 400);
            $this->validate($merchant->status === KycStatus::Pending, 'Hồ sơ Merchant không ở trạng thái chờ duyệt.', 400);

            // 1. Cập nhật trạng thái Merchant Profile
            $this->merchantRepository->updateById($id, [
                'status' => KycStatus::Approved->value
            ]);

            // 2. Cập nhật trạng thái hồ sơ xét duyệt (nếu có)
            $application = $this->driverRegistrationRepository->findActiveApplicationByUser($merchant->user_id, KycType::MERCHANTS);
            if ($application) {
                $this->driverRegistrationRepository->updateStatus($application->id, AppKycStatus::APPROVED);
            }

            // 3. Nâng cấp User role
            $this->userRepository->updateRole($merchant->user_id, UserRole::Merchants);

            event(new \App\Modules\Merchant\Events\MerchantApproved($id, $merchant->user_id));

            return [
                'id'     => $id,
                'status' => KycStatus::Approved->label(),
            ];
        }, useTransaction: true);
    }

    public function rejectMerchant(string $id, string $reason): ServiceReturn
    {
        return $this->execute(function () use ($id, $reason) {
            $merchant = $this->merchantRepository->findById($id);
            $this->validate($merchant !== null, 'Merchant không tồn tại.', 404);
            $this->validate($merchant->status === KycStatus::Pending, 'Hồ sơ Merchant đã được xử lý trước đó.', 400);
            $this->validate(!empty(trim($reason)), 'Vui lòng nhập lý do từ chối.', 400);

            // 1. Cập nhật trạng thái Merchant Profile & Lý do
            $this->merchantRepository->updateById($id, [
                'status'        => KycStatus::Rejected->value,
                'reject_reason' => $reason,
            ]);

            // 2. Cập nhật trạng thái hồ sơ xét duyệt (nếu có)
            $application = $this->driverRegistrationRepository->findActiveApplicationByUser($merchant->user_id, KycType::MERCHANTS);
            if ($application) {
                $this->driverRegistrationRepository->updateStatus($application->id, AppKycStatus::REJECTED, $reason);
            }

            event(new \App\Modules\Merchant\Events\MerchantRejected($id, $merchant->user_id, $reason));

            return [
                'id'     => $id,
                'status' => KycStatus::Rejected->label(),
            ];
        }, useTransaction: true);
    }

    public function toggleLockMerchant(string $id, bool $lock, ?string $reason = null, ?int $lockedDays = null): ServiceReturn
    {
        return $this->execute(function () use ($id, $lock, $reason, $lockedDays) {
            $merchant = $this->merchantRepository->findById($id);
            $this->validate($merchant !== null, 'Merchant không tồn tại.', 404);

            $user = $merchant->user;
            $this->validate($user !== null, 'Không tìm thấy người dùng.', 404);

            // Kiểm tra trạng thái hiện tại (A5)
            $isCurrentlyLocked = !$user->is_active;
            if ($isCurrentlyLocked === $lock) {
                $this->throw('Trạng thái tài khoản đã được cập nhật trước đó.', 400);
            }

            if ($lock) {
                // Logic Khóa (Lock)
                $this->validate(!empty(trim((string)$reason)), 'Vui lòng nhập lý do khóa tài khoản.', 400);
                
                $days = $lockedDays ?? 2;
                $this->validate($days > 0, 'Số ngày khóa không hợp lệ.', 400);

                $lockedAt = now();
                $expiredAt = $lockedAt->copy()->addDays($days);

                $this->userRepository->updateById($user->id, [
                    'is_active'       => false,
                    'lock_reason'     => $reason,
                    'locked_days'     => $days,
                    'locked_at'       => $lockedAt,
                    'lock_expired_at' => $expiredAt,
                ]);
            } else {
                // Logic Mở khóa (Unlock)
                $this->userRepository->updateById($user->id, [
                    'is_active'       => true,
                    'lock_reason'     => null,
                    'locked_days'     => null,
                    'locked_at'       => null,
                    'lock_expired_at' => null,
                ]);
            }

            // Phát sự kiện realtime đồng bộ với Driver/Customer
            UserStatusUpdated::dispatch(
                $user->id,
                !$lock,
                $reason,
                $lock ? $expiredAt->toIso8601String() : null
            );

            event(new \App\Modules\Merchant\Events\MerchantAccountStatusChanged(
                $id, 
                $user->id, 
                $lock, 
                $reason,
                $lock ? ($expiredAt ?? null) : null
            ));

            return [
                'id'        => $id,
                'is_active' => !$lock,
            ];
        }, useTransaction: true);
    }
}
