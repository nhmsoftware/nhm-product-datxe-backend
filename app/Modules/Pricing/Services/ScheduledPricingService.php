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
use Illuminate\Support\Facades\DB;

final class ScheduledPricingService extends BaseService implements ScheduledPricingServiceInterface
{
    public function __construct(
        private readonly ScheduledPricingRepositoryInterface $repository,
        private readonly PricingGlobalSettingRepositoryInterface $globalSettingRepository
    ) {}

    public function getCurrentSettings(): ServiceReturn
    {
        return $this->execute(function () {
            $config = $this->repository->getCurrentConfig();
            $global = $this->globalSettingRepository->getSettings();

            return [
                'pricing' => $config,
                'dispatch_mode' => $global?->scheduled_dispatch_mode?->value ?? ScheduledDispatchMode::INTERNAL_PRIORITY->value,
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
                    $this->repository->update($config->id, $dto->toPricingConfigArray());
                } else {
                    $config = $this->repository->create($dto->toPricingConfigArray());
                }

                // 2. Cập nhật Dispatch Mode trong Global Settings
                $global = $this->globalSettingRepository->getSettings();
                $oldMode = $global?->scheduled_dispatch_mode;
                $newMode = ScheduledDispatchMode::from($dto->dispatchMode);

                if ($global) {
                    $this->globalSettingRepository->update($global->id, [
                        'scheduled_dispatch_mode' => $newMode->value,
                    ]);
                }

                // 3. Phát event nếu chế độ phân phối thay đổi
                if (!$oldMode || $oldMode !== $newMode) {
                    event(new ScheduledRideDispatchModeChanged($newMode, now()->toIso8601String()));
                }

                return [
                    'pricing' => $config->fresh(),
                    'dispatch_mode' => $newMode->value,
                ];
            });
        });
    }
}
