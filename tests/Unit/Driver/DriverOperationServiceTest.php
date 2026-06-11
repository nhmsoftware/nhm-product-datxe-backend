<?php

declare(strict_types=1);

use App\Core\Services\ServiceReturn;
use App\Modules\Driver\DTO\CompleteRideDTO;
use App\Modules\Driver\Events\RideCompleted;
use App\Modules\Driver\Services\DriverOperationService;
use App\Modules\Finance\Model\DriverSubscription;
use App\Modules\Finance\Model\SubscriptionPackage;
use App\Modules\Finance\Model\Wallet;
use App\Modules\Finance\Model\Enums\CommissionServiceType;
use App\Modules\Finance\Model\Enums\CommissionTargetType;
use App\Modules\Finance\Model\Enums\WalletTransactionType;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\Ride\Model\Ride;
use Illuminate\Support\Facades\Event;

uses(Tests\TestCase::class);

afterEach(function () {
    Mockery::close();
});

it('completes ride with dynamic commission and subscription fee reduction then credits wallet', function () {
    Event::fake([RideCompleted::class]);

    $ride = new Ride([
        'driver_id' => 'driver-1',
        'ride_type' => RideType::INTERCITY,
        'status' => RideStatus::IN_PROGRESS,
        'destination_lat' => 10.2,
        'destination_lng' => 106.2,
        'total_price' => 100000,
        'customer_id' => 'customer-1',
    ]);
    $ride->id = '9000001';

    $wallet = new Wallet([
        'user_id' => 'driver-1',
        'balance' => 50000,
        'total_earned' => 200000,
        'total_withdrawn' => 0,
    ]);
    $wallet->id = 'wallet-1';

    $package = new SubscriptionPackage([
        'service_fee_reduction_percent' => 50,
    ]);
    $subscription = new DriverSubscription();
    $subscription->setRelation('package', $package);

    $rideRepository = Mockery::mock(\App\Modules\Ride\Interfaces\RideRepositoryInterface::class);
    $rideRepository->shouldReceive('findById')->once()->with('9000001')->andReturn($ride);
    $rideRepository->shouldReceive('completeTrip')
        ->once()
        ->with('9000001', 100000.0, 5000.0, 95000.0)
        ->andReturn(true);

    $walletRepository = Mockery::mock(\App\Modules\Finance\Interfaces\WalletRepositoryInterface::class);
    $walletRepository->shouldReceive('firstOrCreateForUser')->once()->with('driver-1')->andReturn($wallet);
    $walletRepository->shouldReceive('updateById')
        ->once()
        ->with('wallet-1', [
            'balance' => 145000.0,
            'total_earned' => 295000.0,
        ])
        ->andReturn($wallet);

    $walletTransactionRepository = Mockery::mock(\App\Modules\Finance\Interfaces\WalletTransactionRepositoryInterface::class);
    $walletTransactionRepository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (array $payload) {
            return $payload['wallet_id'] === 'wallet-1'
                && $payload['amount'] === 95000.0
                && $payload['balance_before'] === 50000.0
                && $payload['balance_after'] === 145000.0
                && $payload['reference_id'] === '9000001';
        }))
        ->andReturnTrue();

    $commissionRuleService = Mockery::mock(\App\Modules\Finance\Interfaces\CommissionRuleServiceInterface::class);
    $commissionRuleService->shouldReceive('getApplicableCommission')
        ->once()
        ->with(CommissionTargetType::DRIVER, CommissionServiceType::INTERCITY)
        ->andReturn(ServiceReturn::success([
            'commission_rate' => 10.0,
        ]));

    $driverSubscriptionRepository = Mockery::mock(\App\Modules\Finance\Interfaces\DriverSubscriptionRepositoryInterface::class);
    $driverSubscriptionRepository->shouldReceive('getActiveByDriverId')->once()->with('driver-1')->andReturn($subscription);

    $financeRealtimeService = Mockery::mock(\App\Modules\Finance\Interfaces\FinanceRealtimeInterface::class);
    $financeRealtimeService->shouldReceive('publishWalletEvent')
        ->once();

    $service = new DriverOperationService(
        Mockery::mock(\App\Modules\User\Interfaces\UserRepositoryInterface::class),
        Mockery::mock(\App\Modules\User\Interfaces\DriverProfileRepositoryInterface::class),
        $rideRepository,
        Mockery::mock(\App\Modules\Ride\Interfaces\RideServiceInterface::class),
        Mockery::mock(\App\Modules\Operation\Interfaces\LocationRepositoryInterface::class),
        Mockery::mock(\App\Modules\Finance\Interfaces\VoucherServiceInterface::class),
        Mockery::mock(\App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface::class),
        Mockery::mock(\App\Modules\Food\Interfaces\FoodOrderRepositoryInterface::class),
        $commissionRuleService,
        $driverSubscriptionRepository,
        $walletRepository,
        $walletTransactionRepository,
        $financeRealtimeService,
    );

    $dto = new CompleteRideDTO(
        rideId: '9000001',
        userId: 'driver-1',
        currentLat: 10.2,
        currentLng: 106.2,
    );

    $result = $service->completeRide($dto);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->getData()['commission_rate'])->toBe(5.0)
        ->and($result->getData()['service_fee'])->toBe(5000.0)
        ->and($result->getData()['driver_earnings'])->toBe(95000.0)
        ->and($result->getData()['wallet_balance'])->toBe(145000.0);

    Event::assertDispatched(RideCompleted::class);
});

it('falls back to base 20 percent commission when no active commission rule exists', function () {
    Event::fake([RideCompleted::class]);

    $ride = new Ride([
        'driver_id' => 'driver-2',
        'ride_type' => RideType::CITY,
        'status' => RideStatus::IN_PROGRESS,
        'destination_lat' => 10.2,
        'destination_lng' => 106.2,
        'total_price' => 80000,
        'customer_id' => 'customer-2',
    ]);
    $ride->id = '9000002';

    $wallet = new Wallet([
        'user_id' => 'driver-2',
        'balance' => 0,
        'total_earned' => 0,
        'total_withdrawn' => 0,
    ]);
    $wallet->id = 'wallet-2';

    $rideRepository = Mockery::mock(\App\Modules\Ride\Interfaces\RideRepositoryInterface::class);
    $rideRepository->shouldReceive('findById')->once()->with('9000002')->andReturn($ride);
    $rideRepository->shouldReceive('completeTrip')
        ->once()
        ->with('9000002', 80000.0, 16000.0, 64000.0)
        ->andReturn(true);

    $walletRepository = Mockery::mock(\App\Modules\Finance\Interfaces\WalletRepositoryInterface::class);
    $walletRepository->shouldReceive('firstOrCreateForUser')->once()->with('driver-2')->andReturn($wallet);
    $walletRepository->shouldReceive('updateById')->once()->with('wallet-2', [
        'balance' => 64000.0,
        'total_earned' => 64000.0,
    ])->andReturn($wallet);

    $walletTransactionRepository = Mockery::mock(\App\Modules\Finance\Interfaces\WalletTransactionRepositoryInterface::class);
    $walletTransactionRepository->shouldReceive('create')->once()->andReturnTrue();

    $commissionRuleService = Mockery::mock(\App\Modules\Finance\Interfaces\CommissionRuleServiceInterface::class);
    $commissionRuleService->shouldReceive('getApplicableCommission')
        ->once()
        ->with(CommissionTargetType::DRIVER, CommissionServiceType::RIDE)
        ->andReturn(ServiceReturn::error('not found'));

    $driverSubscriptionRepository = Mockery::mock(\App\Modules\Finance\Interfaces\DriverSubscriptionRepositoryInterface::class);
    $driverSubscriptionRepository->shouldReceive('getActiveByDriverId')->once()->with('driver-2')->andReturn(null);

    $financeRealtimeService = Mockery::mock(\App\Modules\Finance\Interfaces\FinanceRealtimeInterface::class);
    $financeRealtimeService->shouldReceive('publishWalletEvent')->once();

    $service = new DriverOperationService(
        Mockery::mock(\App\Modules\User\Interfaces\UserRepositoryInterface::class),
        Mockery::mock(\App\Modules\User\Interfaces\DriverProfileRepositoryInterface::class),
        $rideRepository,
        Mockery::mock(\App\Modules\Ride\Interfaces\RideServiceInterface::class),
        Mockery::mock(\App\Modules\Operation\Interfaces\LocationRepositoryInterface::class),
        Mockery::mock(\App\Modules\Finance\Interfaces\VoucherServiceInterface::class),
        Mockery::mock(\App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface::class),
        Mockery::mock(\App\Modules\Food\Interfaces\FoodOrderRepositoryInterface::class),
        $commissionRuleService,
        $driverSubscriptionRepository,
        $walletRepository,
        $walletTransactionRepository,
        $financeRealtimeService,
    );

    $dto = new CompleteRideDTO(
        rideId: '9000002',
        userId: 'driver-2',
        currentLat: 10.2,
        currentLng: 106.2,
    );

    $result = $service->completeRide($dto);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->getData()['commission_rate'])->toBe(20.0)
        ->and($result->getData()['service_fee'])->toBe(16000.0)
        ->and($result->getData()['driver_earnings'])->toBe(64000.0);

    Event::assertDispatched(RideCompleted::class);
});
