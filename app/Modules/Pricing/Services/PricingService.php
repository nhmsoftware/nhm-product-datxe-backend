<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Services\BaseService;
use App\Core\Services\ServiceReturn;
use App\Modules\Pricing\DTO\PricingRequestDTO;
use App\Modules\Pricing\DTO\PricingResultDTO;
use App\Modules\Pricing\DTO\PricingConfigDTO;
use App\Modules\Pricing\DTO\ToggleFreeModeDTO;
use App\Modules\Pricing\DTO\UpdatePricingConfigDTO;
use App\Modules\Pricing\DTO\SurgeRuleDTO;
use App\Modules\Pricing\Events\FreeModeToggled;
use App\Modules\Pricing\Events\PricingConfigUpdated;
use App\Modules\Pricing\Interfaces\PricingConfigRepositoryInterface;
use App\Modules\Pricing\Interfaces\PricingConfigHistoryRepositoryInterface;
use App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface;
use App\Modules\Pricing\Interfaces\PricingSurgeRuleRepositoryInterface;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;
use App\Modules\Pricing\Interfaces\ScheduledPricingRepositoryInterface;
use App\Modules\Finance\Interfaces\CommissionRuleServiceInterface;
use App\Modules\Finance\Model\Enums\CommissionServiceType;
use App\Modules\Finance\Model\Enums\CommissionTargetType;
use App\Modules\Ride\Services\VehicleTypeCatalogService;
use Carbon\Carbon;

final class PricingService extends BaseService implements PricingServiceInterface
{
    public function __construct(
        private readonly PricingConfigRepositoryInterface $pricingConfigRepository,
        private readonly PricingConfigHistoryRepositoryInterface $pricingConfigHistoryRepository,
        private readonly PricingGlobalSettingRepositoryInterface $pricingGlobalSettingRepository,
        private readonly PricingSurgeRuleRepositoryInterface $pricingSurgeRuleRepository,
        private readonly ScheduledPricingRepositoryInterface $scheduledPricingRepository,
        private readonly CommissionRuleServiceInterface $commissionRuleService,
        private readonly VehicleTypeCatalogService $vehicleTypeCatalogService,
    ) {}

    private const LEGACY_RATE_CONFIG = [
        1 => [ // BIKE
            'base_fare'     => 12000.0,
            'min_fare'      => 15000.0,
            'distance_rate' => 4000.0,
            'time_rate'     => 300.0,
            'commission_rate' => 20.0,
            'surge_multiplier' => 1.0,
        ],
        2 => [ // CAR_4_SEATS
            'base_fare'     => 25000.0,
            'min_fare'      => 30000.0,
            'distance_rate' => 10000.0,
            'time_rate'     => 500.0,
            'commission_rate' => 20.0,
            'surge_multiplier' => 1.0,
        ],
        3 => [ // CAR_7_SEATS
            'base_fare'     => 30000.0,
            'min_fare'      => 35000.0,
            'distance_rate' => 12000.0,
            'time_rate'     => 600.0,
            'commission_rate' => 20.0,
            'surge_multiplier' => 1.0,
        ],
        4 => [ // CAR_9_SEATS
            'base_fare'     => 40000.0,
            'min_fare'      => 45000.0,
            'distance_rate' => 15000.0,
            'time_rate'     => 700.0,
            'commission_rate' => 20.0,
            'surge_multiplier' => 1.0,
        ],
        6 => [ // CHAUFFEUR
            'base_fare'     => 50000.0,
            'min_fare'      => 60000.0,
            'distance_rate' => 15000.0,
            'time_rate'     => 1000.0,
            'commission_rate' => 25.0,
            'surge_multiplier' => 1.0,
        ],
    ];

    public function calculatePrice(PricingRequestDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): PricingResultDTO {
            $globalSettings = $this->pricingGlobalSettingRepository->getSettings();
            $isFreeMode     = $globalSettings?->is_free_mode ?? false;

            if ($isFreeMode) {
                return PricingResultDTO::create(
                    baseFare:        0.0,
                    distanceFare:    0.0,
                    timeFare:        0.0,
                    surgeMultiplier: 1.0,
                    originalFare:    0.0,
                    finalFare:       0.0,
                );
            }

            $config = $this->resolvePricingConfig($dto);
            $this->validate($config !== null, 'Loại xe này chưa có cấu hình giá đang hoạt động.', 422);

            $baseFare     = (float) $config['base_fare'];
            $minFare      = (float) $config['min_fare'];
            $distanceKm   = (float) $dto->distance;
            $distanceFare = $distanceKm * (float) $config['distance_rate'];
            $timeFare     = (float) $dto->duration * (float) $config['time_rate'];

            // Giá vé cơ bản = Giá cơ bản + (Khoảng cách × Giá/km) + (Thời gian × Giá/phút)
            $baseTotalFare = $baseFare + $distanceFare + $timeFare;

            // Lấy hệ số tăng giá từ các quy tắc động (Dynamic Surge Rules)
            $activeRules = $this->pricingSurgeRuleRepository->getActiveRules((int) $dto->vehicleType);
            $dynamicMultiplier = 1.0;
            $now = now();

            foreach ($activeRules as $rule) {
                $isTimeMatch = true;
                if ($rule->start_time && $rule->end_time) {
                    // Chuyển đổi time string thành đối tượng Carbon để so sánh
                    $startTime = Carbon::createFromTimeString($rule->start_time);
                    $endTime   = Carbon::createFromTimeString($rule->end_time);

                    // Nếu thời gian kết thúc nhỏ hơn bắt đầu (qua đêm), xử lý đặc biệt
                    if ($endTime->lessThan($startTime)) {
                        if (!$now->greaterThanOrEqualTo($startTime) && !$now->lessThanOrEqualTo($endTime)) {
                            $isTimeMatch = false;
                        }
                    } else {
                        if (!$now->between($startTime, $endTime)) {
                            $isTimeMatch = false;
                        }
                    }
                }

                // Hiện tại chúng ta ưu tiên kiểm tra khung giờ.
                // Các điều kiện conditions (JSON) như "weather_rain" sẽ được tích hợp khi có hệ thống sensor/weather API.
                if ($isTimeMatch) {
                    $dynamicMultiplier = max($dynamicMultiplier, (float) $rule->multiplier);
                }
            }

            // Giá vé tăng đột biến = Giá vé cơ bản × Hệ số tăng đột biến
            // Ưu tiên surgeMultiplier cao nhất giữa DTO, Config cố định và Dynamic Rules
            $surgeMultiplier = (float) $dto->surgeMultiplier;
            if ($surgeMultiplier === 1.0 && isset($config['surge_multiplier'])) {
                $surgeMultiplier = (float) $config['surge_multiplier'];
            }

            $surgeMultiplier = max($surgeMultiplier, $dynamicMultiplier);
            $surgeFare = $baseTotalFare * $surgeMultiplier;

            // Giá cuối cùng = max(Giá tăng đột biến, Giá tối thiểu), làm tròn 1000 VND
            $finalFare = round(max($surgeFare, $minFare) / 1000) * 1000;

            // Lấy tỷ lệ hoa hồng động từ module Finance (UC-97)
            $commissionResult = $this->commissionRuleService->getApplicableCommission(
                CommissionTargetType::DRIVER,
                CommissionServiceType::RIDE
            );

            if (!$commissionResult->isError()) {
                $rule = $commissionResult->getData();
                $commissionRate = (float) $rule['commission_rate'];
                $minCommission  = $rule['min_commission'] ? (float) $rule['min_commission'] : 0.0;
                $maxCommission  = $rule['max_commission'] ? (float) $rule['max_commission'] : null;

                $commissionFare = ($finalFare * ($commissionRate / 100));

                if ($minCommission > 0) {
                    $commissionFare = max($commissionFare, $minCommission);
                }
                if ($maxCommission !== null && $maxCommission > 0) {
                    $commissionFare = min($commissionFare, $maxCommission);
                }

                $commissionFare = round($commissionFare / 100) * 100; // Làm tròn 100 VND
            } else {
                // Fallback về config cũ nếu không tìm thấy rule trong Finance
                $commissionRate = (float) ($config['commission_rate'] ?? 20.0);
                $commissionFare = round(($finalFare * ($commissionRate / 100)) / 100) * 100;
            }

            return PricingResultDTO::create(
                baseFare:        $baseFare,
                distanceFare:    $distanceFare,
                timeFare:        $timeFare,
                surgeMultiplier: $surgeMultiplier,
                originalFare:    $baseTotalFare,
                finalFare:       $finalFare,
                commissionRate:  $commissionRate,
                commissionFare:  $commissionFare,
            );
        });
    }

    public function getConfigs(): ServiceReturn
    {
        return $this->execute(function (): array {
            $latestConfigs  = $this->pricingConfigRepository->getAllLatestConfigs()->keyBy('vehicle_type_id');
            $globalSettings = $this->pricingGlobalSettingRepository->getSettings();
            $catalogTypes   = $this->vehicleTypeCatalogService->listAll();

            $finalConfigs = [];
            foreach ($catalogTypes as $type) {
                $config = $latestConfigs->get($type['id']);
                $dto = $config ? PricingConfigDTO::fromModel($config) : null;

                $finalConfigs[] = [
                    'config_id'          => $dto?->id,
                    'vehicle_type_id'    => (int) $type['id'],
                    'vehicle_code'       => $type['code'],
                    'vehicle_label'      => $type['name_vi'],
                    'vehicle_description'=> $type['description_vi'],
                    'service_scopes'     => $type['service_scopes'] ?? [],
                    'is_bookable'        => (bool) ($type['is_bookable'] ?? true),
                    'is_catalog_active'  => (bool) ($type['is_active'] ?? true),
                    'config_status'      => $config === null
                        ? 'not_configured'
                        : ((bool) $config->is_active ? 'configured' : 'inactive'),
                    'is_active'          => (bool) ($config?->is_active ?? false),
                    'base_price'         => $dto?->basePrice,
                    'distance_rate'      => $dto?->distanceRate,
                    'time_rate'          => $dto?->timeRate,
                    'min_fare'           => $dto?->minFare,
                    'surge_multiplier'   => $dto?->surgeMultiplier,
                    'commission_rate'    => $config?->commission_rate !== null ? (float) $config->commission_rate : null,
                ];
            }

            return [
                'configs'        => $finalConfigs,
                'global_settings' => [
                    'is_free_mode' => (bool) ($globalSettings?->is_free_mode ?? false),
                ],
            ];
        });
    }

    public function updateConfig(UpdatePricingConfigDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $vehicleType = $this->vehicleTypeCatalogService->getMetadataById($dto->vehicleTypeId);
            $this->validate($vehicleType !== null, 'Loại xe không tồn tại.', 404);

            $config = $this->pricingConfigRepository->findLatestByVehicleTypeId($dto->vehicleTypeId);

            $data = [
                'vehicle_type_id'  => $dto->vehicleTypeId,
                'base_price'       => $dto->basePrice,
                'distance_rate'    => $dto->distanceRate,
                'time_rate'        => $dto->timeRate,
                'min_fare'         => $dto->minFare,
                'surge_multiplier' => $dto->surgeMultiplier,
                'commission_rate'  => $dto->commissionRate,
                'is_active'        => $dto->isActive,
            ];

            if ($config) {
                $oldData = $config->toArray();
                $this->pricingConfigRepository->updateById($config->id, $data);
                event(new PricingConfigUpdated($dto->vehicleTypeId, $oldData, $data, $dto->adminId));
            } else {
                $this->pricingConfigRepository->create($data);
                event(new PricingConfigUpdated($dto->vehicleTypeId, [], $data, $dto->adminId));
            }

            return ['status' => 'success'];
        }, useTransaction: true);
    }

    public function toggleFreeMode(ToggleFreeModeDTO $dto): ServiceReturn
    {
        return $this->execute(function () use ($dto): array {
            $settings = $this->pricingGlobalSettingRepository->getSettings();

            if ($settings) {
                $this->pricingGlobalSettingRepository->updateById($settings->id, [
                    'is_free_mode' => $dto->isFreeMode,
                ]);
            } else {
                $this->pricingGlobalSettingRepository->create([
                    'is_free_mode' => $dto->isFreeMode,
                ]);
            }

            event(new FreeModeToggled($dto->isFreeMode));

            return ['is_free_mode' => $dto->isFreeMode];
        }, useTransaction: true);
    }

    public function getSurgeRules(): ServiceReturn
    {
        return $this->execute(function (): array {
            $rules = $this->pricingSurgeRuleRepository->getAllRules();
            return $rules->toArray();
        });
    }

    public function saveSurgeRule(SurgeRuleDTO $dto, ?string $ruleId = null): ServiceReturn
    {
        return $this->execute(function () use ($dto, $ruleId): array {
            $data = $dto->toArray();

            // Chỉ update nếu ruleId có giá trị thực (không phải null hoặc chuỗi rỗng)
            if (!empty($ruleId)) {
                $this->pricingSurgeRuleRepository->updateById($ruleId, $data);
            } else {
                $this->pricingSurgeRuleRepository->create($data);
            }

            return ['status' => 'success'];
        }, useTransaction: true);
    }

    public function deleteSurgeRule(string $ruleId): ServiceReturn
    {
        return $this->execute(function () use ($ruleId): array {
            $this->pricingSurgeRuleRepository->deleteById($ruleId);
            return ['status' => 'success'];
        }, useTransaction: true);
    }

    public function resetToDefault(int $vehicleType): ServiceReturn
    {
        return $this->execute(function () use ($vehicleType): array {
            $config = $this->pricingConfigRepository->findLatestByVehicleTypeId($vehicleType);
            if ($config) {
                $this->pricingConfigRepository->updateById($config->id, ['is_active' => false]);
            }
            return ['status' => 'success'];
        }, useTransaction: true);
    }

    public function archiveConfig(int $vehicleTypeId): ServiceReturn
    {
        return $this->execute(function () use ($vehicleTypeId): array {
            $config = $this->pricingConfigRepository->findLatestByVehicleTypeId($vehicleTypeId);
            $this->validate($config !== null, 'Không tìm thấy cấu hình giá để lưu trữ.', 404);

            $oldData = $config->toArray();
            $this->pricingConfigRepository->updateById($config->id, ['is_active' => false]);
            event(new PricingConfigUpdated($vehicleTypeId, $oldData, array_merge($oldData, ['is_active' => false]), (string) request()->user()?->id));

            return ['status' => 'success'];
        }, useTransaction: true);
    }

    public function getPricingHistory(int $vehicleType): ServiceReturn
    {
        return $this->execute(function () use ($vehicleType) {
            return $this->pricingConfigHistoryRepository->getByVehicleTypeId($vehicleType);
        });
    }

    private function getConfigForVehicleType(int $vehicleType): array
    {
        $metadata = $this->vehicleTypeCatalogService->getMetadataById($vehicleType);
        if ($metadata === null || !($metadata['is_active'] ?? false) || !($metadata['is_bookable'] ?? false)) {
            return null;
        }

        $dbConfig = $this->pricingConfigRepository->findActiveByVehicleTypeId($vehicleType);
        if ($dbConfig) {
            return [
                'base_fare'        => (float) $dbConfig->base_price,
                'min_fare'         => (float) $dbConfig->min_fare,
                'distance_rate'    => (float) $dbConfig->distance_rate,
                'time_rate'        => (float) $dbConfig->time_rate,
                'surge_multiplier' => (float) $dbConfig->surge_multiplier,
                'commission_rate'  => (float) $dbConfig->commission_rate,
            ];
        }

        return self::LEGACY_RATE_CONFIG[$vehicleType] ?? null;
    }

    private function resolvePricingConfig(PricingRequestDTO $dto): ?array
    {
        $scheduledConfig = $this->getScheduledRuleConfig($dto);
        if ($scheduledConfig !== null) {
            return $scheduledConfig;
        }

        $vehicleTypeId = (int) $dto->vehicleType;
        $metadata = $this->vehicleTypeCatalogService->getMetadataById($vehicleTypeId);
        if ($metadata === null || !($metadata['is_active'] ?? false) || !($metadata['is_bookable'] ?? false)) {
            return null;
        }

        $dbConfig = $this->pricingConfigRepository->findActiveByVehicleTypeId($vehicleTypeId);
        if ($dbConfig) {
            return [
                'base_fare'        => (float) $dbConfig->base_price,
                'min_fare'         => (float) $dbConfig->min_fare,
                'distance_rate'    => (float) $dbConfig->distance_rate,
                'time_rate'        => (float) $dbConfig->time_rate,
                'surge_multiplier' => (float) $dbConfig->surge_multiplier,
                'commission_rate'  => (float) $dbConfig->commission_rate,
            ];
        }

        if (!$dto->allowLegacyFallback) {
            return null;
        }

        return self::LEGACY_RATE_CONFIG[$vehicleTypeId] ?? null;
    }

    private function getScheduledRuleConfig(PricingRequestDTO $dto): ?array
    {
        if ($dto->serviceType === null || $dto->rideMode === null) {
            return null;
        }

        $rule = $this->scheduledPricingRepository->findMatchingRule(
            serviceType: $dto->serviceType,
            rideMode: $dto->rideMode,
            vehicleTypeId: (int) $dto->vehicleType,
            airportId: $dto->airportId,
        );

        if ($rule === null) {
            return null;
        }

        $distanceKm = (float) $dto->distance;
        $matchedRange = $rule->ranges
            ->where('is_active', true)
            ->sortBy('start_km')
            ->first(function ($range) use ($distanceKm) {
                $startKm = (float) $range->start_km;
                $endKm = (float) $range->end_km;

                if ($distanceKm < $startKm) {
                    return false;
                }

                return $endKm <= 0 || $distanceKm <= $endKm;
            });

        if ($matchedRange === null) {
            return null;
        }

        $fixedFare = (float) $matchedRange->price;

        return [
            'base_fare' => $fixedFare,
            'min_fare' => $fixedFare,
            'distance_rate' => 0.0,
            'time_rate' => 0.0,
            'surge_multiplier' => 1.0,
            'commission_rate' => 20.0,
        ];
    }
}
