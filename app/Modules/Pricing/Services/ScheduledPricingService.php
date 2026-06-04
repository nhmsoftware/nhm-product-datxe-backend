<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Pricing\DTO\UpdateScheduledPricingDTO;
use App\Modules\Pricing\Events\ScheduledRideDispatchModeChanged;
use App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface;
use App\Modules\Pricing\Interfaces\ScheduledPricingRepositoryInterface;
use App\Modules\Pricing\Interfaces\ScheduledPricingServiceInterface;
use App\Modules\Pricing\Model\Enums\ScheduledDispatchMode;
use App\Modules\Ride\Interfaces\RideRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class ScheduledPricingService extends BaseService implements ScheduledPricingServiceInterface
{
    public function __construct(
        private readonly ScheduledPricingRepositoryInterface $repository,
        private readonly PricingGlobalSettingRepositoryInterface $globalSettingRepository,
        private readonly RideRepositoryInterface $rideRepository,
    ) {}

    public function getCurrentSettings(): ServiceReturn
    {
        return $this->execute(function () {
            $config = $this->repository->getCurrentConfig();
            $global = $this->globalSettingRepository->getSettings();

            $dispatchMode = $global?->scheduled_dispatch_mode?->value ?? ScheduledDispatchMode::INTERNAL_PRIORITY->value;

            return [
                'pricing'             => $config,
                'dispatch_mode'       => $dispatchMode,
                'dispatch_mode_label' => $dispatchMode === ScheduledDispatchMode::OPEN_POOL->value
                    ? 'Tự động (Tài xế nhận chuyến)'
                    : 'Admin phân phối (Thủ công)',
                'is_admin_controlled' => $dispatchMode === ScheduledDispatchMode::INTERNAL_PRIORITY->value,
                'auto_push_internal'  => (bool) ($global?->auto_push_internal ?? false),
            ];
        });
    }

    public function updateSettings(UpdateScheduledPricingDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto) {
            return DB::transaction(function () use ($dto) {
                // 1. Cập nhật hoặc tạo mới cấu hình giá
                $config = $this->repository->getCurrentConfig();
                if ($config) {
                    $this->repository->updateById($config->id, $dto->toPricingConfigArray());
                } else {
                    $config = $this->repository->create($dto->toPricingConfigArray());
                }

                // 2. Cập nhật Dispatch Mode trong Global Settings
                $global = $this->globalSettingRepository->getSettings();
                $oldMode = $global?->scheduled_dispatch_mode;
                $newMode = ScheduledDispatchMode::from($dto->dispatchMode);

                if ($global) {
                    $this->globalSettingRepository->updateById($global->id, [
                        'scheduled_dispatch_mode' => $newMode->value,
                    ]);
                }

                // 3. Phát event nếu chế độ phân phối thay đổi
                if (!$oldMode || $oldMode !== $newMode) {
                    event(new ScheduledRideDispatchModeChanged($newMode, now()->toIso8601String()));
                }

                return [
                    'pricing'       => $config->fresh(),
                    'dispatch_mode' => $newMode->value,
                ];
            });
        });
    }

    /**
     * Bật/Tắt chế độ phân phối chuyến đặt trước.
     *
     * Cơ chế:
     *   - mode = 1 (INTERNAL_PRIORITY / BẬT Admin control):
     *       Chuyến xe sẽ KHÔNG hiển thị cho tài xế. Admin tự tay gán tài xế.
     *       → Tự động ẩn toàn bộ chuyến đang chờ khỏi pool.
     *
     *   - mode = 2 (OPEN_POOL / TẮT Admin control):
     *       Chuyến xe tự động hiển thị cho tài xế phù hợp nhận.
     *       → Tự động đẩy toàn bộ chuyến đang chờ ra pool.
     */
    public function toggleDispatchMode(int $mode): ServiceReturn
    {
        return $this->execute(function () use ($mode) {
            $newMode = ScheduledDispatchMode::from($mode);

            // 1. Lấy cấu hình hiện tại
            $global = $this->globalSettingRepository->getSettings();
            $oldMode = $global?->scheduled_dispatch_mode;

            // 2. Cập nhật DB
            if ($global) {
                $this->globalSettingRepository->updateById($global->id, [
                    'scheduled_dispatch_mode' => $newMode->value,
                ]);
            } else {
                $this->globalSettingRepository->create([
                    'is_free_mode'            => false,
                    'scheduled_dispatch_mode' => $newMode->value,
                ]);
            }

            // 3. Phát event nếu mode thay đổi
            if ($oldMode !== $newMode) {
                event(new ScheduledRideDispatchModeChanged($newMode, now()->toIso8601String()));
            }

            $affectedRides = 0;

            // 4. Xử lý tự động pool khi chuyển mode
            if ($newMode === ScheduledDispatchMode::OPEN_POOL) {
                // Tắt Admin Priority → Tự động đẩy toàn bộ chuyến đang chờ ra pool
                $affectedRides = $this->rideRepository->pushAllPendingScheduledToPool();
            } elseif ($newMode === ScheduledDispatchMode::INTERNAL_PRIORITY) {
                // Bật Admin Priority → Ẩn toàn bộ chuyến khỏi pool
                $affectedRides = $this->rideRepository->hideAllPendingScheduledFromPool();
            }

            return [
                'dispatch_mode'       => $newMode->value,
                'dispatch_mode_label' => $newMode === ScheduledDispatchMode::OPEN_POOL
                    ? 'Tự động (Tài xế nhận chuyến)'
                    : 'Admin phân phối (Thủ công)',
                'is_admin_controlled' => $newMode === ScheduledDispatchMode::INTERNAL_PRIORITY,
                'affected_rides'      => $affectedRides,
                'message'             => $newMode === ScheduledDispatchMode::OPEN_POOL
                    ? "Đã bật chế độ tự động. {$affectedRides} chuyến đặt trước đã được đẩy cho tài xế."
                    : "Đã bật chế độ Admin phân phối. {$affectedRides} chuyến đặt trước đã được ẩn khỏi pool tài xế.",
            ];
        });
    }

    public function toggleInternalAutoPush(bool $isAutoPush): ServiceReturn
    {
        return $this->execute(function () use ($isAutoPush) {
            $global = $this->globalSettingRepository->getSettings();
            if ($global) {
                $this->globalSettingRepository->updateById($global->id, [
                    'auto_push_internal' => $isAutoPush,
                ]);
            } else {
                $this->globalSettingRepository->create([
                    'is_free_mode'            => false,
                    'scheduled_dispatch_mode' => ScheduledDispatchMode::INTERNAL_PRIORITY->value,
                    'auto_push_internal'      => $isAutoPush,
                ]);
            }

            $affectedRides = 0;
            if ($isAutoPush) {
                $affectedRides = $this->rideRepository->pushAllPendingScheduledToInternalPool();
            } else {
                $affectedRides = $this->rideRepository->hideAllPendingScheduledFromInternalPool();
            }

            return [
                'auto_push_internal' => $isAutoPush,
                'affected_rides'     => $affectedRides,
                'message'            => $isAutoPush
                    ? "Đã bật chế độ tự động phát chuyến nội bộ. {$affectedRides} chuyến đã được đẩy cho đội xe nhà."
                    : "Đã tắt tự động phát chuyến nội bộ. {$affectedRides} chuyến đã được thu hồi khỏi đội xe nhà.",
            ];
        });
    }
}
