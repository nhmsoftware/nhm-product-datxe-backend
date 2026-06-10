<?php

declare(strict_types=1);

use App\Modules\Ride\DTO\AdminCreateDeliveryOrderDTO;
use App\Modules\Ride\DTO\AdminUpdateDeliveryOrderDTO;
use App\Modules\Ride\Events\RideCanceled;
use App\Modules\Ride\Http\Requests\AdminCreateDeliveryOrderRequest;
use App\Modules\Ride\Model\DeliveryOrder;
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

it('validates create delivery order request', function () {
    $request = AdminCreateDeliveryOrderRequest::create('/api/v1/admin/services', 'POST', [
        'sender_name' => '',
        'sender_phone' => 'abc',
        'pickup_address' => 'Same place',
        'destination_address' => 'Same place',
        'goods_type' => '',
        'total_price' => -1,
    ]);

    $validator = Validator::make($request->all(), $request->rules(), $request->messages());
    $request->withValidator($validator);
    $validator->passes();

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('sender_name'))->toBeTrue()
        ->and($validator->errors()->has('sender_phone'))->toBeTrue()
        ->and($validator->errors()->has('goods_type'))->toBeTrue()
        ->and($validator->errors()->has('total_price'))->toBeTrue()
        ->and($validator->errors()->has('destination_address'))->toBeTrue();
});

it('creates admin delivery order in pending state', function () {
    $admin = new User([
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $admin->id = '9101';
    app('request')->setUserResolver(fn () => $admin);

    $customer = new User([
        'phone' => '0900000003',
        'role' => UserRole::Customer,
        'is_active' => true,
        'is_verified' => true,
        'is_phone_verified' => true,
    ]);
    $customer->id = '1101';
    $customer->deleted_at = null;
    $customer->setRelation('customerProfile', (object) ['full_name' => 'Sender']);

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
        'vehicle_type' => 1,
        'ride_type' => RideType::DELIVERY,
        'status' => RideStatus::PENDING,
        'tracking_status' => RideTrackingStatus::WAITING_DRIVER,
        'base_price' => 50000,
        'total_price' => 50000,
    ]);
    $ride->id = '5101';

    $deliveryOrder = new DeliveryOrder([
        'ride_id' => '5101',
        'sender_name' => 'Sender',
        'sender_phone' => '0900000003',
        'receiver_name' => 'Receiver',
        'receiver_phone' => '0900000004',
        'goods_type' => 'Documents',
        'goods_weight' => 0.1,
        'goods_note' => 'Fragile',
        'is_fragile' => false,
    ]);
    $ride->setRelation('deliveryOrder', $deliveryOrder);

    $rideRepository = \Mockery::mock(\App\Modules\Ride\Interfaces\RideRepositoryInterface::class);
    $rideRepository->shouldReceive('create')->once()->andReturn($ride);
    $rideRepository->shouldReceive('createDeliveryOrderDetail')->once();
    $rideRepository->shouldReceive('findById')->once()->andReturn($ride);

    $userRepository = \Mockery::mock(\App\Modules\User\Interfaces\UserRepositoryInterface::class);
    $userRepository->shouldReceive('findByPhone')->once()->andReturn($customer);

    $pricingGlobalSettingRepository = \Mockery::mock(\App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface::class);
    $pricingGlobalSettingRepository->shouldReceive('getSettings')->andReturn(null);

    $service = new RideService(
        $rideRepository,
        \Mockery::mock(\App\Modules\Ride\Interfaces\MapServiceInterface::class),
        \Mockery::mock(\App\Modules\Pricing\Interfaces\PricingServiceInterface::class),
        new \App\Modules\Ride\Services\VehicleTypeCatalogService(\Mockery::mock(\App\Modules\Ride\Interfaces\VehicleTypeRepositoryInterface::class)),
        $userRepository,
        \Mockery::mock(\App\Modules\User\Interfaces\DriverProfileRepositoryInterface::class),
        \Mockery::mock(\App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface::class),
        \Mockery::mock(\App\Modules\Ride\Interfaces\AirportRepositoryInterface::class),
        \Mockery::mock(\App\Modules\RiskManagement\Interfaces\CancellationConfigServiceInterface::class),
        $pricingGlobalSettingRepository,
        \Mockery::mock(\App\Modules\Finance\Interfaces\VoucherServiceInterface::class)
    );

    $dto = new AdminCreateDeliveryOrderDTO(
        senderName: 'Sender',
        senderPhone: '0900000003',
        pickupAddress: 'Pickup',
        pickupLat: 10.1,
        pickupLng: 106.1,
        receiverName: 'Receiver',
        receiverPhone: '0900000004',
        destinationAddress: 'Destination',
        destinationLat: 10.2,
        destinationLng: 106.2,
        goodsType: 'Documents',
        goodsNote: 'Fragile',
        totalPrice: 50000,
        distanceKm: 5,
        durationMinutes: 10,
        driverId: null,
    );

    $result = $service->createAdminDeliveryOrder($dto);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->getMessage())->toBe('Tạo đơn giao hàng thành công.');
});

it('returns delivery order to pending when admin removes assigned driver', function () {
    $admin = new User([
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $admin->id = '9102';
    app('request')->setUserResolver(fn () => $admin);

    $ride = new Ride([
        'customer_id' => '1101',
        'driver_id' => '5001',
        'pickup_address' => 'Pickup',
        'pickup_lat' => 10.1,
        'pickup_lng' => 106.1,
        'destination_address' => 'Destination',
        'destination_lat' => 10.2,
        'destination_lng' => 106.2,
        'distance' => 5000,
        'duration' => 600,
        'vehicle_type' => 1,
        'ride_type' => RideType::DELIVERY,
        'status' => RideStatus::ACCEPTED,
        'tracking_status' => RideTrackingStatus::DRIVER_ACCEPTED,
        'base_price' => 50000,
        'total_price' => 50000,
    ]);
    $ride->id = '5201';
    $deliveryOrder = new DeliveryOrder([
        'ride_id' => '5201',
        'sender_name' => 'Sender',
        'sender_phone' => '0900000003',
        'receiver_name' => 'Receiver',
        'receiver_phone' => '0900000004',
        'goods_type' => 'Documents',
        'goods_weight' => 0.1,
    ]);
    $ride->setRelation('deliveryOrder', $deliveryOrder);

    $releasedRide = clone $ride;
    $releasedRide->driver_id = null;
    $releasedRide->status = RideStatus::PENDING;
    $releasedRide->tracking_status = RideTrackingStatus::WAITING_DRIVER;
    $releasedRide->setRelation('deliveryOrder', $deliveryOrder);

    $rideRepository = \Mockery::mock(\App\Modules\Ride\Interfaces\RideRepositoryInterface::class);
    $rideRepository->shouldReceive('findById')->once()->andReturn($ride);
    $rideRepository->shouldReceive('updateById')->once()->andReturn($ride);
    $rideRepository->shouldReceive('findById')->once()->andReturn($ride);
    $rideRepository->shouldReceive('releaseDriverFromRide')->once()->with('5201', 'Admin unassigned driver from delivery order');
    $rideRepository->shouldReceive('updateStatus')->once()->with('5201', RideStatus::PENDING);
    $rideRepository->shouldReceive('findById')->once()->andReturn($releasedRide);

    $service = new RideService(
        $rideRepository,
        \Mockery::mock(\App\Modules\Ride\Interfaces\MapServiceInterface::class),
        \Mockery::mock(\App\Modules\Pricing\Interfaces\PricingServiceInterface::class),
        new \App\Modules\Ride\Services\VehicleTypeCatalogService(\Mockery::mock(\App\Modules\Ride\Interfaces\VehicleTypeRepositoryInterface::class)),
        \Mockery::mock(\App\Modules\User\Interfaces\UserRepositoryInterface::class),
        \Mockery::mock(\App\Modules\User\Interfaces\DriverProfileRepositoryInterface::class),
        \Mockery::mock(\App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface::class),
        \Mockery::mock(\App\Modules\Ride\Interfaces\AirportRepositoryInterface::class),
        \Mockery::mock(\App\Modules\RiskManagement\Interfaces\CancellationConfigServiceInterface::class),
        \Mockery::mock(\App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface::class),
        \Mockery::mock(\App\Modules\Finance\Interfaces\VoucherServiceInterface::class)
    );

    $dto = new AdminUpdateDeliveryOrderDTO(
        rideId: '5201',
        senderName: 'Sender',
        senderPhone: '0900000003',
        pickupAddress: 'Pickup',
        pickupLat: 10.1,
        pickupLng: 106.1,
        receiverName: 'Receiver',
        receiverPhone: '0900000004',
        destinationAddress: 'Destination',
        destinationLat: 10.2,
        destinationLng: 106.2,
        goodsType: 'Documents',
        goodsNote: 'Note',
        totalPrice: 60000,
        distanceKm: 5,
        durationMinutes: 10,
        driverId: null,
    );

    $result = $service->updateAdminDeliveryOrder($dto);

    expect($result->isSuccess())->toBeTrue()
        ->and($result->getData()->status)->toBe(RideStatus::PENDING);
});

it('soft cancels delivery order and dispatches driver notification event', function () {
    Event::fake([RideCanceled::class]);

    $admin = new User([
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);
    $admin->id = '9103';
    app('request')->setUserResolver(fn () => $admin);

    $ride = new Ride([
        'customer_id' => '1101',
        'driver_id' => '5001',
        'pickup_address' => 'Pickup',
        'pickup_lat' => 10.1,
        'pickup_lng' => 106.1,
        'destination_address' => 'Destination',
        'destination_lat' => 10.2,
        'destination_lng' => 106.2,
        'distance' => 5000,
        'duration' => 600,
        'vehicle_type' => 1,
        'ride_type' => RideType::DELIVERY,
        'status' => RideStatus::ACCEPTED,
        'tracking_status' => RideTrackingStatus::DRIVER_ACCEPTED,
        'base_price' => 50000,
        'total_price' => 50000,
    ]);
    $ride->id = '5301';

    $rideRepository = \Mockery::mock(\App\Modules\Ride\Interfaces\RideRepositoryInterface::class);
    $rideRepository->shouldReceive('findById')->once()->andReturn($ride);
    $rideRepository->shouldReceive('cancel')->once()->with('5301', 'Receiver unavailable', 0);

    $service = new RideService(
        $rideRepository,
        \Mockery::mock(\App\Modules\Ride\Interfaces\MapServiceInterface::class),
        \Mockery::mock(\App\Modules\Pricing\Interfaces\PricingServiceInterface::class),
        new \App\Modules\Ride\Services\VehicleTypeCatalogService(\Mockery::mock(\App\Modules\Ride\Interfaces\VehicleTypeRepositoryInterface::class)),
        \Mockery::mock(\App\Modules\User\Interfaces\UserRepositoryInterface::class),
        \Mockery::mock(\App\Modules\User\Interfaces\DriverProfileRepositoryInterface::class),
        \Mockery::mock(\App\Modules\Ride\Interfaces\RideTrackingRealtimeInterface::class),
        \Mockery::mock(\App\Modules\Ride\Interfaces\AirportRepositoryInterface::class),
        \Mockery::mock(\App\Modules\RiskManagement\Interfaces\CancellationConfigServiceInterface::class),
        \Mockery::mock(\App\Modules\Pricing\Interfaces\PricingGlobalSettingRepositoryInterface::class),
        \Mockery::mock(\App\Modules\Finance\Interfaces\VoucherServiceInterface::class)
    );

    $result = $service->cancelAdminDeliveryOrder(new \App\Modules\Ride\DTO\AdminCancelRideBookingDTO(
        rideId: '5301',
        reason: 'Receiver unavailable',
    ));

    expect($result->isSuccess())->toBeTrue()
        ->and($result->getData()['status'])->toBe(RideStatus::CANCELLED->value);

    Event::assertDispatched(RideCanceled::class);
});

it('maps delivery admin resource with delivery info', function () {
    $ride = new Ride([
        'customer_id' => '1101',
        'pickup_address' => 'Pickup',
        'pickup_lat' => 10.1,
        'pickup_lng' => 106.1,
        'destination_address' => 'Destination',
        'destination_lat' => 10.2,
        'destination_lng' => 106.2,
        'distance' => 5000,
        'duration' => 600,
        'vehicle_type' => 1,
        'ride_type' => RideType::DELIVERY,
        'status' => RideStatus::PENDING,
        'tracking_status' => RideTrackingStatus::WAITING_DRIVER,
        'base_price' => 50000,
        'total_price' => 50000,
    ]);
    $ride->id = '5401';
    $ride->setRelation('customer', (object) [
        'full_name' => 'Sender',
        'phone' => '0900000003',
    ]);
    $ride->setRelation('driver', null);
    $ride->setRelation('deliveryOrder', new DeliveryOrder([
        'sender_name' => 'Sender',
        'sender_phone' => '0900000003',
        'receiver_name' => 'Receiver',
        'receiver_phone' => '0900000004',
        'goods_type' => 'Documents',
        'goods_weight' => 0.1,
        'goods_note' => 'Handle with care',
        'is_fragile' => false,
    ]));

    $data = (new \App\Modules\Ride\Http\Resources\AdminServiceOrderResource($ride))->toArray(Request::create('/'));

    expect($data['is_delivery'])->toBeTrue()
        ->and($data['status'])->toBe('waiting')
        ->and($data['delivery_info']['sender_name'])->toBe('Sender')
        ->and($data['can_cancel'])->toBeTrue();
});
