<?php

declare(strict_types=1);

use App\Core\Services\ServiceReturn;
use App\Modules\Order\DTO\AdminCreateFoodOrderDTO;
use App\Modules\Order\DTO\AdminUpdateFoodOrderDTO;
use App\Modules\Order\Http\Requests\AdminCreateFoodOrderRequest;
use App\Modules\Order\Services\AdminOrderService;
use App\Modules\Food\Model\Enums\FoodOrderStatus;
use App\Modules\Food\Model\FoodOrder;
use App\Modules\Merchant\Model\MenuItem;
use App\Modules\Ride\Model\Enums\RideStatus;
use App\Modules\Ride\Model\Enums\RideTrackingStatus;
use App\Modules\Ride\Model\Enums\RideType;
use App\Modules\Ride\Model\Enums\VehicleType;
use App\Modules\Ride\Model\Ride;
use App\Modules\User\Model\Enums\UserRole;
use App\Modules\User\Model\MerchantProfile;
use App\Modules\User\Model\User;
use Illuminate\Support\Facades\Validator;

uses(Tests\TestCase::class);

afterEach(function () {
    \Mockery::close();
});

it('validates create food order request', function () {
    $request = AdminCreateFoodOrderRequest::create('/api/v1/admin/services/orders', 'POST', [
        'customer_name' => '',
        'customer_phone' => 'abc',
        'merchant_id' => '',
        'delivery_address' => '',
        'subtotal_price' => -1,
        'delivery_fee' => -1,
        'service_fee' => -1,
        'total_price' => -1,
        'items' => [],
    ]);

    $validator = Validator::make($request->all(), $request->rules(), $request->messages());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('customer_name'))->toBeTrue()
        ->and($validator->errors()->has('customer_phone'))->toBeTrue()
        ->and($validator->errors()->has('merchant_id'))->toBeTrue()
        ->and($validator->errors()->has('items'))->toBeTrue();
});

it('builds admin food order dto correctly', function () {
    $dto = new AdminCreateFoodOrderDTO(
        customerName: 'Customer',
        customerPhone: '0900000002',
        merchantId: '2001',
        items: [[
            'menu_item_id' => '3001',
            'quantity' => 1,
            'notes' => 'Less ice',
            'options' => [],
        ]],
        deliveryAddress: 'Customer address',
        deliveryLat: null,
        deliveryLng: null,
        notes: 'Less ice',
        subtotalPrice: 50000,
        deliveryFee: 10000,
        serviceFee: 2000,
        totalPrice: 62000,
        distanceKm: null,
        durationMinutes: null,
        driverId: null,
    );

    expect($dto->customerName)->toBe('Customer')
        ->and($dto->merchantId)->toBe('2001')
        ->and($dto->items[0]['menu_item_id'])->toBe('3001');
});

it('rejects unavailable food item', function () {
    $admin = new User(['role' => UserRole::Admin, 'is_active' => true]);
    $admin->id = '9302';
    app('request')->setUserResolver(fn () => $admin);

    $merchant = new MerchantProfile(['is_open' => true]);
    $merchant->id = '2002';

    $menuItem = new MenuItem([
        'is_available' => false,
    ]);
    $menuItem->id = '3002';

    $merchantRepository = \Mockery::mock(\App\Modules\Merchant\Interfaces\MerchantRepositoryInterface::class);
    $merchantRepository->shouldReceive('findById')->once()->andReturn($merchant);

    $menuRepository = \Mockery::mock(\App\Modules\Merchant\Interfaces\MenuItemRepositoryInterface::class);
    $menuRepository->shouldReceive('findItem')->once()->andReturn($menuItem);

    $userRepository = \Mockery::mock(\App\Modules\User\Interfaces\UserRepositoryInterface::class);

    $service = new AdminOrderService(
        \Mockery::mock(\App\Modules\Food\Interfaces\FoodOrderRepositoryInterface::class),
        $merchantRepository,
        $menuRepository,
        $userRepository,
        \Mockery::mock(\App\Modules\Ride\Interfaces\RideRepositoryInterface::class),
        \Mockery::mock(\App\Modules\Ride\Interfaces\RideServiceInterface::class)
    );

    $dto = new AdminCreateFoodOrderDTO(
        customerName: 'Customer',
        customerPhone: '0900000002',
        merchantId: '2002',
        items: [[
            'menu_item_id' => '3002',
            'quantity' => 1,
            'notes' => null,
            'options' => [],
        ]],
        deliveryAddress: 'Customer address',
        deliveryLat: null,
        deliveryLng: null,
        notes: null,
        subtotalPrice: 50000,
        deliveryFee: 10000,
        serviceFee: 2000,
        totalPrice: 62000,
        distanceKm: null,
        durationMinutes: null,
        driverId: null,
    );

    $result = $service->createFoodOrder($dto);

    expect($result->isError())->toBeTrue()
        ->and($result->getMessage())->toBe('Món ăn hiện không khả dụng.');
});
