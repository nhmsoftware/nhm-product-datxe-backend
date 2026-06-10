<?php

declare(strict_types=1);

use App\Core\Services\ServiceReturn;
use App\Modules\Ride\DTO\AdminCreateRideBookingDTO;
use App\Modules\Ride\DTO\AdminUpdateRideBookingDTO;
use App\Modules\Ride\Http\Requests\AdminCreateRideBookingRequest;
use App\Modules\Ride\Events\RideCanceled;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideTrackingStatus;
use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\Ride\Model\Ride;
use App\Modules\Ride\Services\RideService;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;

uses(Tests\TestCase::class);

afterEach(function () {
    \Mockery::close();
});

it('validates create ride booking for existing customer mode', function () {
    $request = AdminCreateRideBookingRequest::create('/api/v1/admin/rides/scheduled', 'POST', [
        'ride_type' => 1,
        'customer_mode' => 'existing',
        'customer_id' => null,
        'pickup_address' => 'A',
        'destination_address' => 'B',
        'vehicle_type_id' => 1,
        'total_price' => 100000,
    ]);

    $validator = Validator::make($request->all(), $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('customer_id'))->toBeTrue();
});

it('validates create ride booking for new customer mode and location rules', function () {
    $request = AdminCreateRideBookingRequest::create('/api/v1/admin/rides/scheduled', 'POST', [
        'ride_type' => 4,
        'customer_mode' => 'new',
        'customer_name' => 'Nguyen Van A',
        'customer_phone' => '0900000001',
        'pickup_address' => 'Same place',
        'destination_address' => 'Same place',
        'pickup_lat' => 10.0,
        'pickup_lng' => 106.0,
        'destination_lat' => 10.0,
        'destination_lng' => 106.0,
        'vehicle_type_id' => 1,
        'total_price' => 100000,
    ]);

    $validator = Validator::make($request->all(), $request->rules(), $request->messages());
    $validator->after(fn () => null);
    $request->withValidator($validator);
    $validator->passes();

    expect($validator->errors()->has('ride_type'))->toBeTrue()
        ->and($validator->errors()->has('destination_address'))->toBeTrue();
});

it('creates admin ride booking in pending state', function () {
    $admin = new User([
        'phone' => '0900000001',
        'email' => 'admin@example.com',
        'password' => 'hashed-password',
        'role' => UserRole::Admin,
        'is_verified' => true,
        'is_phone_verified' => true,
        'is_active' => true,
    ]);
    $admin->id = '9001';
    app('request')->setUserResolver(fn () => $admin);

    $customer = new User([
        'phone' => '0900000002',
        'email' => 'customer@example.com',
        'password' => 'hashed-password',
        'role' => UserRole::Customer,
        'is_verified' => true,
        'is_phone_verified' => true,
        'is_active' => true,
    ]);
    $customer->id = '1001';

    $ride = new Ride([
        'customer_id' => $customer->id,
        'pickup_address' => 'Pickup',
        'pickup_lat' => 10.1,
        'pickup_lng' => 106.1,
        'destination_address' => 'Destination',
        'destination_lat' => 10.2,
        'destination_lng' => 106.2,
        'distance' => 5000,
        'duration' => 600,
        'vehicle_type' => 2,
        'ride_type' => RideType::CITY,
        'status' => RideStatus::PENDING,
        'tracking_status' => RideTrackingStatus::WAITING_DRIVER,
        'base_price' => 100000,
        'total_price' => 100000,
        'is_paid' => false,
        'is_pushed_to_pool' => false,
    ]);
    $ride->id = '1234567890';

    $rideRepository = Mockery::mock(\App\Modules\Ride\Interfaces\RideRepositoryInterface::class);
    $rideRepository->shouldReceive('create')->once()->andReturn($ride);
    $rideRepository->shouldReceive('findById')->once()->andReturn($ride);

    $userRepository = Mockery::mock(\App\Modules\User\Interfaces\UserRepositoryInterface::class);
    $userRepository->shouldReceive('findDetailById')->andReturn($customer);

    $mapService = Mockery::mock(\App\Modules\Ride\Interfaces\MapServiceInterface::class);
    $pricingService = Mockery::mock(\App\Modules\Pricing\Interfaces\PricingServiceInterface::class);
    $driverProfileRepository = Mockery::mock(\App\Modules\User\Interfaces\DriverProfileRepositoryInterface::class);
    $realtime = Mockery::mock(\App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface::class);
    $airportRepository = Mockery::mock(\App\Modules\Ride\Interfaces\AirportRepositoryInterface::class);
    $cancellationConfigService = Mockery::mock(\App\Modules\RiskManagement\Interfaces\CancellationConfigServiceInterface::class);
    $pricingGlobalSettingRepository = Mockery::mock(\App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface::class);
    $pricingGlobalSettingRepository->shouldReceive('getSettings')->andReturn(null);
    $voucherService = Mockery::mock(\App\Modules\Finance\Interfaces\VoucherServiceInterface::class);

    $service = new RideService(
        $rideRepository,
        $mapService,
        $pricingService,
        new \App\Modules\Ride\Services\VehicleTypeCatalogService(\Mockery::mock(\App\Modules\Ride\Interfaces\VehicleTypeRepositoryInterface::class)),
        $userRepository,
        $driverProfileRepository,
        $realtime,
        $airportRepository,
        $cancellationConfigService,
        $pricingGlobalSettingRepository,
        $voucherService
    );

    $dto = new AdminCreateRideBookingDTO(
        rideType: 1,
        customerMode: 'existing',
        customerId: (string) $customer->id,
        customerName: null,
        customerPhone: null,
        customerEmail: null,
        pickupAddress: 'Pickup',
        pickupLat: 10.1,
        pickupLng: 106.1,
        destinationAddress: 'Destination',
        destinationLat: 10.2,
        destinationLng: 106.2,
        vehicleType: 2,
        totalPrice: 100000,
        distanceKm: 5,
        durationMinutes: 10,
        driverId: null,
        travelDate: null,
        travelTime: null,
        airportId: null,
        airportDirection: null,
    );

    $result = $service->createAdminRideBooking($dto);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->getMessage())->toBe('Tạo chuyến xe thành công.')
        ->and($result->getData())->toBeInstanceOf(Ride::class);
});

it('returns completed-state error when admin updates a completed ride', function () {
    $admin = new User([
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $admin->id = '9002';
    app('request')->setUserResolver(fn () => $admin);

    $completedRide = new Ride([
        'customer_id' => '1001',
        'pickup_address' => 'Pickup',
        'pickup_lat' => 10.1,
        'pickup_lng' => 106.1,
        'destination_address' => 'Destination',
        'destination_lat' => 10.2,
        'destination_lng' => 106.2,
        'distance' => 5000,
        'duration' => 600,
        'vehicle_type' => 2,
        'ride_type' => RideType::CITY,
        'status' => RideStatus::COMPLETED,
        'tracking_status' => RideTrackingStatus::DRIVER_ACCEPTED,
        'base_price' => 100000,
        'total_price' => 100000,
    ]);
    $completedRide->id = '2001';

    $rideRepository = Mockery::mock(\App\Modules\Ride\Interfaces\RideRepositoryInterface::class);
    $rideRepository->shouldReceive('findById')->once()->andReturn($completedRide);

    $service = new RideService(
        $rideRepository,
        Mockery::mock(\App\Modules\Ride\Interfaces\MapServiceInterface::class),
        Mockery::mock(\App\Modules\Pricing\Interfaces\PricingServiceInterface::class),
        new \App\Modules\Ride\Services\VehicleTypeCatalogService(\Mockery::mock(\App\Modules\Ride\Interfaces\VehicleTypeRepositoryInterface::class)),
        Mockery::mock(\App\Modules\User\Interfaces\UserRepositoryInterface::class),
        Mockery::mock(\App\Modules\User\Interfaces\DriverProfileRepositoryInterface::class),
        Mockery::mock(\App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface::class),
        Mockery::mock(\App\Modules\Ride\Interfaces\AirportRepositoryInterface::class),
        Mockery::mock(\App\Modules\RiskManagement\Interfaces\CancellationConfigServiceInterface::class),
        Mockery::mock(\App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface::class),
        Mockery::mock(\App\Modules\Finance\Interfaces\VoucherServiceInterface::class)
    );

    $dto = new AdminUpdateRideBookingDTO(
        rideId: '2001',
        rideType: 1,
        pickupAddress: 'Pickup',
        pickupLat: 10.1,
        pickupLng: 106.1,
        destinationAddress: 'Destination',
        destinationLat: 10.2,
        destinationLng: 106.2,
        vehicleType: 2,
        totalPrice: 120000,
        distanceKm: 5,
        durationMinutes: 10,
        driverId: null,
        travelDate: null,
        travelTime: null,
        airportId: null,
        airportDirection: null,
    );

    $result = $service->updateAdminRideBooking($dto);

    expect($result->isError())->toBeTrue()
        ->and($result->getMessage())->toBe('Không thể cập nhật chuyến xe ở trạng thái hiện tại.')
        ->and($result->getCode())->toBe(400);
});

it('returns ride to pending when admin removes assigned driver', function () {
    $admin = new User([
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $admin->id = '9003';
    app('request')->setUserResolver(fn () => $admin);

    $assignedRide = new Ride([
        'customer_id' => '1001',
        'driver_id' => '5001',
        'pickup_address' => 'Pickup',
        'pickup_lat' => 10.1,
        'pickup_lng' => 106.1,
        'destination_address' => 'Destination',
        'destination_lat' => 10.2,
        'destination_lng' => 106.2,
        'distance' => 5000,
        'duration' => 600,
        'vehicle_type' => 2,
        'ride_type' => RideType::CITY,
        'status' => RideStatus::ACCEPTED,
        'tracking_status' => RideTrackingStatus::DRIVER_ACCEPTED,
        'base_price' => 100000,
        'total_price' => 100000,
    ]);
    $assignedRide->id = '3001';

    $releasedRide = clone $assignedRide;
    $releasedRide->driver_id = null;
    $releasedRide->status = RideStatus::PENDING;
    $releasedRide->tracking_status = RideTrackingStatus::WAITING_DRIVER;

    $rideRepository = Mockery::mock(\App\Modules\Ride\Interfaces\RideRepositoryInterface::class);
    $rideRepository->shouldReceive('findById')
        ->once()
        ->andReturn($assignedRide);
    $rideRepository->shouldReceive('updateById')
        ->once()
        ->andReturn($assignedRide);
    $rideRepository->shouldReceive('findById')
        ->once()
        ->andReturn($assignedRide);
    $rideRepository->shouldReceive('releaseDriverFromRide')
        ->once()
        ->with('3001', 'Admin unassigned driver');
    $rideRepository->shouldReceive('updateStatus')
        ->once()
        ->with('3001', RideStatus::PENDING);
    $rideRepository->shouldReceive('findById')
        ->once()
        ->andReturn($releasedRide);

    $service = new RideService(
        $rideRepository,
        Mockery::mock(\App\Modules\Ride\Interfaces\MapServiceInterface::class),
        Mockery::mock(\App\Modules\Pricing\Interfaces\PricingServiceInterface::class),
        new \App\Modules\Ride\Services\VehicleTypeCatalogService(\Mockery::mock(\App\Modules\Ride\Interfaces\VehicleTypeRepositoryInterface::class)),
        Mockery::mock(\App\Modules\User\Interfaces\UserRepositoryInterface::class),
        Mockery::mock(\App\Modules\User\Interfaces\DriverProfileRepositoryInterface::class),
        Mockery::mock(\App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface::class),
        Mockery::mock(\App\Modules\Ride\Interfaces\AirportRepositoryInterface::class),
        Mockery::mock(\App\Modules\RiskManagement\Interfaces\CancellationConfigServiceInterface::class),
        Mockery::mock(\App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface::class),
        Mockery::mock(\App\Modules\Finance\Interfaces\VoucherServiceInterface::class)
    );

    $dto = new AdminUpdateRideBookingDTO(
        rideId: '3001',
        rideType: 1,
        pickupAddress: 'Pickup',
        pickupLat: 10.1,
        pickupLng: 106.1,
        destinationAddress: 'Destination',
        destinationLat: 10.2,
        destinationLng: 106.2,
        vehicleType: 2,
        totalPrice: 120000,
        distanceKm: 5,
        durationMinutes: 10,
        driverId: null,
        travelDate: null,
        travelTime: null,
        airportId: null,
        airportDirection: null,
    );

    $result = $service->updateAdminRideBooking($dto);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->getData()->status)->toBe(RideStatus::PENDING);
});

it('soft cancels admin ride booking and notifies assigned driver', function () {
    Event::fake([RideCanceled::class]);

    $admin = new User([
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $admin->id = '9004';
    app('request')->setUserResolver(fn () => $admin);

    $ride = new Ride([
        'customer_id' => '1001',
        'driver_id' => '5001',
        'pickup_address' => 'Pickup',
        'pickup_lat' => 10.1,
        'pickup_lng' => 106.1,
        'destination_address' => 'Destination',
        'destination_lat' => 10.2,
        'destination_lng' => 106.2,
        'distance' => 5000,
        'duration' => 600,
        'vehicle_type' => 2,
        'ride_type' => RideType::CITY,
        'status' => RideStatus::ACCEPTED,
        'tracking_status' => RideTrackingStatus::DRIVER_ACCEPTED,
        'base_price' => 100000,
        'total_price' => 100000,
    ]);
    $ride->id = '4001';

    $rideRepository = Mockery::mock(\App\Modules\Ride\Interfaces\RideRepositoryInterface::class);
    $rideRepository->shouldReceive('findById')->once()->andReturn($ride);
    $rideRepository->shouldReceive('cancel')->once()->with('4001', 'Customer no longer needs ride', 0);

    $service = new RideService(
        $rideRepository,
        Mockery::mock(\App\Modules\Ride\Interfaces\MapServiceInterface::class),
        Mockery::mock(\App\Modules\Pricing\Interfaces\PricingServiceInterface::class),
        new \App\Modules\Ride\Services\VehicleTypeCatalogService(\Mockery::mock(\App\Modules\Ride\Interfaces\VehicleTypeRepositoryInterface::class)),
        Mockery::mock(\App\Modules\User\Interfaces\UserRepositoryInterface::class),
        Mockery::mock(\App\Modules\User\Interfaces\DriverProfileRepositoryInterface::class),
        Mockery::mock(\App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface::class),
        Mockery::mock(\App\Modules\Ride\Interfaces\AirportRepositoryInterface::class),
        Mockery::mock(\App\Modules\RiskManagement\Interfaces\CancellationConfigServiceInterface::class),
        Mockery::mock(\App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface::class),
        Mockery::mock(\App\Modules\Finance\Interfaces\VoucherServiceInterface::class)
    );

    $dto = new \App\Modules\Ride\DTO\AdminCancelRideBookingDTO(
        rideId: '4001',
        reason: 'Customer no longer needs ride',
    );

    $result = $service->cancelAdminRideBooking($dto);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->getData()['status'])->toBe(RideStatus::CANCELLED->value);

    Event::assertDispatched(RideCanceled::class, function (RideCanceled $event) {
        return $event->rideId === '4001'
            && $event->driverId === '5001'
            && $event->canceledBy === 'admin';
    });
});

it('returns a reusable admin ride resource shape', function () {
    $customerStub = (object) [
        'id' => '1',
        'full_name' => 'Test Customer',
        'phone' => '0900000001',
        'email' => 'test@example.com',
    ];

    $ride = new Ride([
        'customer_id' => '1',
        'pickup_address' => 'Pickup',
        'pickup_lat' => 10.1,
        'pickup_lng' => 106.1,
        'destination_address' => 'Destination',
        'destination_lat' => 10.2,
        'destination_lng' => 106.2,
        'distance' => 5000,
        'duration' => 600,
        'vehicle_type' => 2,
        'ride_type' => RideType::CITY,
        'status' => RideStatus::PENDING,
        'tracking_status' => RideTrackingStatus::WAITING_DRIVER,
        'base_price' => 100000,
        'total_price' => 100000,
        'is_paid' => false,
        'is_pushed_to_pool' => false,
    ]);
    $ride->id = '123';
    $ride->setRelation('customer', $customerStub);
    $ride->setRelation('driver', null);

    $resource = new \App\Modules\Ride\Http\Resources\AdminScheduledRideResource($ride);
    $data = $resource->toArray(Request::create('/'));

    expect($data['status'])->toBe('waiting')
        ->and($data['can_edit'])->toBeTrue()
        ->and($data['can_cancel'])->toBeTrue();
});
