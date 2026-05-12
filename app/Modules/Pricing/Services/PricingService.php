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
use App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface;
use App\Modules\Pricing\Interfaces\PricingSurgeRuleRepositoryInterface;
use App\Modules\Pricing\Interfaces\PricingServiceInterface;
use App\Modules\Finance\Interfaces\CommissionRuleServiceInterface;
use App\Modules\Finance\Model\Enums\CommissionServiceType;

final class PricingService extends BaseService implements PricingServiceInterface
{
    public function __construct(
        private readonly PricingConfigRepositoryInterface $pricingConfigRepository,
        private readonly PricingGlobalSettingRepositoryInterface $pricingGlobalSettingRepository,
        private readonly PricingSurgeRuleRepositoryInterface $pricingSurgeRuleRepository,
        private readonly CommissionRuleServiceInterface $commissionRuleService,
    ) {}

    private const RATE_CONFIG = [
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

            $config = $this->getConfigForVehicleType((int) $dto->vehicleType);

            $baseFare     = (float) $config['base_fare'];
            $minFare      = (float) $config['min_fare'];
            $distanceKm   = (float) $dto->distance / 1000;
            $distanceFare = $distanceKm * (float) $config['distance_rate'];
            $timeFare     = ((float) $dto->duration / 60) * (float) $config['time_rate'];

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
                    $startTime = \Carbon\Carbon::createFromTimeString($rule->start_time);
                    $endTime   = \Carbon\Carbon::createFromTimeString($rule->end_time);
                    
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
            $commissionResult = $this->commissionRuleService->getApplicableCommission(CommissionServiceType::RIDE);
            
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
            $dbConfigs      = $this->pricingConfigRepository->getAllConfigs()->keyBy('vehicle_type');
            $globalSettings = $this->pricingGlobalSettingRepository->getSettings();

            $finalConfigs = [];
            // Đảm bảo luôn trả về 4 loại xe (1: Bike, 2: Car4, 3: Car7, 4: Car9)
            for ($i = 1; $i <= 4; $i++) {
                $config = $dbConfigs->get($i);
                
                if ($config) {
                    $dto = PricingConfigDTO::fromModel($config);
                    // Ép kiểu vehicleType về số để Frontend dễ xử lý
                    $finalConfigs[] = [
                        'vehicle_type'     => $i,
                        'base_price'       => (float) $dto->basePrice,
                        'distance_rate'    => (float) $dto->distanceRate,
                        'time_rate'        => (float) $dto->timeRate,
                        'min_fare'         => (float) $dto->minFare,
                        'surge_multiplier' => (float) $dto->surgeMultiplier,
                    ];
                } else {
                    $default = self::RATE_CONFIG[$i];
                    $finalConfigs[] = [
                        'vehicle_type'     => $i,
                        'base_price'       => (float) $default['base_fare'],
                        'distance_rate'    => (float) $default['distance_rate'],
                        'time_rate'        => (float) $default['time_rate'],
                        'min_fare'         => (float) $default['min_fare'],
                        'surge_multiplier' => (float) $default['surge_multiplier'],
                    ];
                }
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
            $config = $this->pricingConfigRepository->findByVehicleType($dto->vehicleType->value);

            $data = [
                'vehicle_type'     => $dto->vehicleType->value,
                'base_price'       => $dto->basePrice,
                'distance_rate'    => $dto->distanceRate,
                'time_rate'        => $dto->timeRate,
                'min_fare'         => $dto->minFare,
                'surge_multiplier' => $dto->surgeMultiplier,
            ];

            if ($config) {
                $oldData = $config->toArray();
                $this->pricingConfigRepository->updateById($config->id, $data);
                event(new PricingConfigUpdated($dto->vehicleType->value, $oldData, $data));
            } else {
                $this->pricingConfigRepository->create($data);
                event(new PricingConfigUpdated($dto->vehicleType->value, [], $data));
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
            $config = $this->pricingConfigRepository->findByVehicleType($vehicleType);
            if ($config) {
                $this->pricingConfigRepository->deleteById($config->id);
            }
            return ['status' => 'success'];
        }, useTransaction: true);
    }

    private function getConfigForVehicleType(int $vehicleType): array
    {
        $dbConfig = $this->pricingConfigRepository->findByVehicleType($vehicleType);
        if ($dbConfig) {
            return [
                'base_fare'        => (float) $dbConfig->base_price,
                'min_fare'         => (float) $dbConfig->min_fare,
                'distance_rate'    => (float) $dbConfig->distance_rate,
                'time_rate'        => (float) $dbConfig->time_rate,
                'surge_multiplier' => (float) $dbConfig->surge_multiplier,
            ];
        }

        return self::RATE_CONFIG[$vehicleType] ?? self::RATE_CONFIG[1];
    }
}
