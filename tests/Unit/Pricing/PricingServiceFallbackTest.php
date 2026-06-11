<?php

declare(strict_types=1);

use App\Core\Services\ServiceReturn;
use App\Modules\Finance\Model\Enums\CommissionServiceType;
use App\Modules\Finance\Model\Enums\CommissionTargetType;
use App\Modules\Pricing\DTO\PricingRequestDTO;
use App\Modules\Pricing\Model\PricingConfig;
use App\Modules\Pricing\Model\PricingGlobalSetting;
use App\Modules\Pricing\Model\ScheduledPricingRange;
use App\Modules\Pricing\Model\ScheduledPricingRule;
use App\Modules\Pricing\Services\PricingService;
use App\Modules\Ride\Services\VehicleTypeCatalogService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

uses(Tests\TestCase::class);

afterEach(function () {
    Mockery::close();
});

it('applies scheduled pricing rule when rule matches before per-km config', function () {
    $scheduledRule = new ScheduledPricingRule([
        'service_type' => 6,
        'ride_mode' => 'private',
        'vehicle_type_id' => 2,
        'airport_id' => null,
        'is_active' => true,
    ]);
    $scheduledRule->setRelation('ranges', collect([
        new ScheduledPricingRange([
            'start_km' => 0,
            'end_km' => 100,
            'price' => 180000,
            'unit' => 'per_trip',
            'is_active' => true,
        ]),
    ]));

    $pricingConfigRepository = Mockery::mock(\App\Modules\Pricing\Interfaces\PricingConfigRepositoryInterface::class);
    $pricingConfigRepository->shouldReceive('findActiveByVehicleTypeId')->never();

    $scheduledPricingRepository = Mockery::mock(\App\Modules\Pricing\Interfaces\ScheduledPricingRepositoryInterface::class);
    $scheduledPricingRepository->shouldReceive('findMatchingRule')
        ->once()
        ->with(6, 'private', 2, null)
        ->andReturn($scheduledRule);

    $globalSettingRepository = Mockery::mock(\App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface::class);
    $globalSettingRepository->shouldReceive('getSettings')->andReturn(new PricingGlobalSetting(['is_free_mode' => false]));

    $surgeRuleRepository = Mockery::mock(\App\Modules\Pricing\Interfaces\PricingSurgeRuleRepositoryInterface::class);
    $surgeRuleRepository->shouldReceive('getActiveRules')->once()->with(2)->andReturn(new EloquentCollection());

    $commissionRuleService = Mockery::mock(\App\Modules\Finance\Interfaces\CommissionRuleServiceInterface::class);
    $commissionRuleService->shouldReceive('getApplicableCommission')
        ->once()
        ->with(CommissionTargetType::DRIVER, CommissionServiceType::RIDE)
        ->andReturn(ServiceReturn::error('No dynamic commission'));

    $vehicleTypeRepository = Mockery::mock(\App\Modules\Ride\Interfaces\VehicleTypeRepositoryInterface::class);
    $vehicleTypeCatalog = new VehicleTypeCatalogService($vehicleTypeRepository);

    $service = new PricingService(
        $pricingConfigRepository,
        Mockery::mock(\App\Modules\Pricing\Interfaces\PricingConfigHistoryRepositoryInterface::class),
        $globalSettingRepository,
        $surgeRuleRepository,
        $scheduledPricingRepository,
        $commissionRuleService,
        $vehicleTypeCatalog,
    );

    $result = $service->calculatePrice(PricingRequestDTO::create(
        distance: 50,
        duration: 120,
        vehicleType: 2,
        serviceType: 6,
        rideMode: 'private',
        allowLegacyFallback: false,
    ));

    expect($result->isSuccess())->toBeTrue();
    expect($result->getData()->finalFare)->toBe(180000.0);
});

it('falls back to pricing config per-km when no scheduled rule matches', function () {
    $pricingConfig = new PricingConfig([
        'vehicle_type_id' => 2,
        'base_price' => 25000,
        'distance_rate' => 10000,
        'time_rate' => 500,
        'min_fare' => 30000,
        'surge_multiplier' => 1,
        'commission_rate' => 20,
        'is_active' => true,
    ]);

    $pricingConfigRepository = Mockery::mock(\App\Modules\Pricing\Interfaces\PricingConfigRepositoryInterface::class);
    $pricingConfigRepository->shouldReceive('findActiveByVehicleTypeId')->once()->with(2)->andReturn($pricingConfig);

    $scheduledPricingRepository = Mockery::mock(\App\Modules\Pricing\Interfaces\ScheduledPricingRepositoryInterface::class);
    $scheduledPricingRepository->shouldReceive('findMatchingRule')
        ->once()
        ->with(6, 'private', 2, null)
        ->andReturn(null);

    $globalSettingRepository = Mockery::mock(\App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface::class);
    $globalSettingRepository->shouldReceive('getSettings')->andReturn(new PricingGlobalSetting(['is_free_mode' => false]));

    $surgeRuleRepository = Mockery::mock(\App\Modules\Pricing\Interfaces\PricingSurgeRuleRepositoryInterface::class);
    $surgeRuleRepository->shouldReceive('getActiveRules')->once()->with(2)->andReturn(new EloquentCollection());

    $commissionRuleService = Mockery::mock(\App\Modules\Finance\Interfaces\CommissionRuleServiceInterface::class);
    $commissionRuleService->shouldReceive('getApplicableCommission')
        ->once()
        ->with(CommissionTargetType::DRIVER, CommissionServiceType::RIDE)
        ->andReturn(ServiceReturn::error('No dynamic commission'));

    $vehicleTypeRepository = Mockery::mock(\App\Modules\Ride\Interfaces\VehicleTypeRepositoryInterface::class);
    $vehicleTypeCatalog = new VehicleTypeCatalogService($vehicleTypeRepository);

    $service = new PricingService(
        $pricingConfigRepository,
        Mockery::mock(\App\Modules\Pricing\Interfaces\PricingConfigHistoryRepositoryInterface::class),
        $globalSettingRepository,
        $surgeRuleRepository,
        $scheduledPricingRepository,
        $commissionRuleService,
        $vehicleTypeCatalog,
    );

    $result = $service->calculatePrice(PricingRequestDTO::create(
        distance: 10,
        duration: 20,
        vehicleType: 2,
        serviceType: 6,
        rideMode: 'private',
        allowLegacyFallback: false,
    ));

    expect($result->isSuccess())->toBeTrue();
    expect($result->getData()->finalFare)->toBe(135000.0);
});

it('uses legacy fallback or blocks when no scheduled rule and no active config remain', function () {
    $pricingConfigRepository = Mockery::mock(\App\Modules\Pricing\Interfaces\PricingConfigRepositoryInterface::class);
    $pricingConfigRepository->shouldReceive('findActiveByVehicleTypeId')->twice()->with(2)->andReturn(null);

    $scheduledPricingRepository = Mockery::mock(\App\Modules\Pricing\Interfaces\ScheduledPricingRepositoryInterface::class);
    $scheduledPricingRepository->shouldReceive('findMatchingRule')->twice()->with(6, 'private', 2, null)->andReturn(null);

    $globalSettingRepository = Mockery::mock(\App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface::class);
    $globalSettingRepository->shouldReceive('getSettings')->twice()->andReturn(new PricingGlobalSetting(['is_free_mode' => false]));

    $surgeRuleRepository = Mockery::mock(\App\Modules\Pricing\Interfaces\PricingSurgeRuleRepositoryInterface::class);
    $surgeRuleRepository->shouldReceive('getActiveRules')->once()->with(2)->andReturn(new EloquentCollection());

    $commissionRuleService = Mockery::mock(\App\Modules\Finance\Interfaces\CommissionRuleServiceInterface::class);
    $commissionRuleService->shouldReceive('getApplicableCommission')
        ->once()
        ->with(CommissionTargetType::DRIVER, CommissionServiceType::RIDE)
        ->andReturn(ServiceReturn::error('No dynamic commission'));

    $vehicleTypeRepository = Mockery::mock(\App\Modules\Ride\Interfaces\VehicleTypeRepositoryInterface::class);
    $vehicleTypeCatalog = new VehicleTypeCatalogService($vehicleTypeRepository);

    $service = new PricingService(
        $pricingConfigRepository,
        Mockery::mock(\App\Modules\Pricing\Interfaces\PricingConfigHistoryRepositoryInterface::class),
        $globalSettingRepository,
        $surgeRuleRepository,
        $scheduledPricingRepository,
        $commissionRuleService,
        $vehicleTypeCatalog,
    );

    $legacyResult = $service->calculatePrice(PricingRequestDTO::create(
        distance: 10,
        duration: 20,
        vehicleType: 2,
        serviceType: 6,
        rideMode: 'private',
        allowLegacyFallback: true,
    ));

    expect($legacyResult->isSuccess())->toBeTrue();
    expect($legacyResult->getData()->finalFare)->toBe(135000.0);

    $blockedResult = $service->calculatePrice(PricingRequestDTO::create(
        distance: 10,
        duration: 20,
        vehicleType: 2,
        serviceType: 6,
        rideMode: 'private',
        allowLegacyFallback: false,
    ));

    expect($blockedResult->isError())->toBeTrue();
    expect($blockedResult->getCode())->toBe(422);
});
